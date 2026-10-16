<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\BillingList;
use App\Models\MyClass;
use App\Models\StudentRecord;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function index()
    {
        $myClasses = MyClass::schoolOrder()->get();
        $gradeOptions = $this->buildGradeOptions($myClasses);

        $studentCountsByClass = StudentRecord::query()
            ->selectRaw('my_class_id, COUNT(*) as total')
            ->where('grad', 0)
            ->groupBy('my_class_id')
            ->pluck('total', 'my_class_id');

        $studentCountsByGrade = [];
        foreach ($gradeOptions as $grade) {
            $studentCountsByGrade[$grade['key']] = collect($grade['class_ids'])
                ->sum(function ($classId) use ($studentCountsByClass) {
                    return (int) ($studentCountsByClass[$classId] ?? 0);
                });
        }

        $existingLists = BillingList::whereIn('grade_key', collect($gradeOptions)->pluck('key')->all())
            ->get()
            ->keyBy('grade_key');

        return response()->view('pages.super_admin.billings.index', [
            'grade_options' => $gradeOptions,
            'student_counts_by_grade' => $studentCountsByGrade,
            'existing_lists' => $existingLists,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function showList(Request $request, $grade_key)
    {
        $grade = $this->findGradeOptionByKey($grade_key);
        if (!$grade) {
            abort(404);
        }

        $billingList = BillingList::firstOrCreate(
            ['grade_key' => $grade['key']],
            [
                'grade_label' => $grade['label'],
                'layout' => json_encode($this->defaultLayout()),
            ]
        );

        $layout = $billingList->layout ? json_decode($billingList->layout, true) : [];
        if (!is_array($layout) || !isset($layout['columns'], $layout['rows'])) {
            $layout = $this->defaultLayout();
        }

        $students = StudentRecord::query()
            ->select('student_records.*')
            ->join('users', 'users.id', '=', 'student_records.user_id')
            ->whereIn('student_records.my_class_id', $grade['class_ids'])
            ->where('student_records.grad', 0)
            ->with(['user', 'section'])
            ->orderBy('users.name', 'asc')
            ->get();

        return view('pages.super_admin.billings.list', [
            'grade' => $grade,
            'billing_list' => $billingList,
            'layout' => $layout,
            'students' => $students,
            'editable' => $request->boolean('edit'),
        ]);
    }

    public function saveList(Request $request, $grade_key)
    {
        $grade = $this->findGradeOptionByKey($grade_key);
        if (!$grade) {
            return response()->json(['ok' => false, 'msg' => 'Selected grade is invalid.'], 422);
        }

        $validated = $request->validate([
            'layout' => 'required|array',
            'layout.columns' => 'required|array|min:2',
            'layout.rows' => 'required|array|min:2',
        ]);

        $normalized = $this->normalizeLayout($validated['layout']);

        $billingList = BillingList::firstOrCreate(
            ['grade_key' => $grade['key']],
            [
                'grade_label' => $grade['label'],
                'layout' => json_encode($this->defaultLayout()),
            ]
        );

        $billingList->grade_label = $grade['label'];
        $billingList->layout = json_encode($normalized);
        $billingList->save();

        return response()->json([
            'ok' => true,
            'msg' => 'Billing list progress saved successfully.',
            'updated_at' => $billingList->updated_at ? $billingList->updated_at->toDateTimeString() : null,
        ]);
    }

    private function buildGradeOptions($myClasses)
    {
        $grouped = [
            'k' => [
                'key' => 'k',
                'label' => 'Kinder',
                'class_ids' => [],
            ],
        ];

        for ($i = 1; $i <= 12; $i++) {
            $key = (string) $i;
            $grouped[$key] = [
                'key' => $key,
                'label' => 'Grade '.$key,
                'class_ids' => [],
            ];
        }

        foreach ($myClasses as $class) {
            $key = $this->gradeKeyFromClassName($class->name);
            if (!$key) {
                continue;
            }

            $grouped[$key]['class_ids'][] = (int) $class->id;
        }

        return collect($grouped)->sortBy(function ($item) {
            if ($item['key'] === 'k') {
                return 0;
            }

            return (int) $item['key'];
        })->values()->all();
    }

    private function findGradeOptionByKey($gradeKey)
    {
        return collect($this->buildGradeOptions(MyClass::schoolOrder()->get()))
            ->first(function ($grade) use ($gradeKey) {
                return (string) $grade['key'] === (string) strtolower($gradeKey);
            });
    }

    private function gradeKeyFromClassName($className)
    {
        $name = strtolower(trim((string) $className));

        if (preg_match('/^(k|kinder|kindergarten)\b/', $name)) {
            return 'k';
        }

        if (preg_match('/^(?:grade\s*)?(1[0-2]|[1-9])\b/', $name, $matches)) {
            return (string) ((int) $matches[1]);
        }

        return null;
    }

    private function gradeLabelFromKey($key)
    {
        return $key === 'k' ? 'Kinder' : 'Grade '.$key;
    }

    private function defaultLayout()
    {
        return [
            'columns' => [
                ['key' => 'col_1', 'title' => 'Column 1'],
                ['key' => 'col_2', 'title' => 'Column 2'],
            ],
            'rows' => [
                ['id' => 'row_1', 'cells' => ['col_1' => '', 'col_2' => '']],
                ['id' => 'row_2', 'cells' => ['col_1' => '', 'col_2' => '']],
            ],
        ];
    }

    private function normalizeLayout(array $layout)
    {
        $columns = collect($layout['columns'] ?? [])->map(function ($column, $index) {
            $key = isset($column['key']) && is_string($column['key']) && trim($column['key']) !== ''
                ? trim($column['key'])
                : 'col_'.($index + 1);

            $title = isset($column['title']) && is_string($column['title'])
                ? trim($column['title'])
                : '';

            return [
                'key' => $key,
                'title' => $title,
            ];
        })->filter(function ($column) {
            return $column['key'] !== '';
        })->values()->all();

        if (count($columns) < 2) {
            $columns = $this->defaultLayout()['columns'];
        }

        $columnKeys = collect($columns)->pluck('key')->all();

        $rows = collect($layout['rows'] ?? [])->map(function ($row, $index) use ($columnKeys) {
            $rowId = isset($row['id']) && is_string($row['id']) && trim($row['id']) !== ''
                ? trim($row['id'])
                : 'row_'.($index + 1);

            $cells = [];
            foreach ($columnKeys as $key) {
                $value = '';
                if (isset($row['cells']) && is_array($row['cells']) && array_key_exists($key, $row['cells'])) {
                    $value = is_string($row['cells'][$key]) ? $row['cells'][$key] : '';
                }
                $cells[$key] = $value;
            }

            return [
                'id' => $rowId,
                'cells' => $cells,
            ];
        })->values()->all();

        if (count($rows) < 2) {
            $rows = $this->defaultLayout()['rows'];
            foreach ($rows as $rIndex => $row) {
                $normalizedCells = [];
                foreach ($columnKeys as $cIndex => $key) {
                    $defaultKey = 'col_'.($cIndex + 1);
                    $normalizedCells[$key] = $row['cells'][$defaultKey] ?? '';
                }
                $rows[$rIndex]['cells'] = $normalizedCells;
            }
        }

        return [
            'columns' => $columns,
            'rows' => $rows,
        ];
    }
}
