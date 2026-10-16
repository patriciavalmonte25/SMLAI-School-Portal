<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Requests\MyClass\ClassCreate;
use App\Http\Requests\MyClass\ClassUpdate;
use App\Repositories\MyClassRepo;
use App\Repositories\UserRepo;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MyClassController extends Controller
{
    protected $my_class, $user;

    public function __construct(MyClassRepo $my_class, UserRepo $user)
    {
        $this->middleware('teamSAOrRegistrar', ['except' => ['destroy',] ]);
        $this->middleware('administrator', ['only' => ['destroy',] ]);
        $this->middleware('administratorOrRegistrar', ['only' => ['deleteSelected',] ]);

        $this->my_class = $my_class;
        $this->user = $user;
    }

    public function index()
    {
        $d['my_classes'] = $this->my_class->all();
        $d['class_types'] = $this->my_class->getTypes();

        return view('pages.support_team.classes.index', $d);
    }

    public function store(ClassCreate $req)
    {
        $data = $req->all();
        $mc = $this->my_class->create($data);

        // Create Default Section
        $s =['my_class_id' => $mc->id,
            'name' => 'A',
            'active' => 1,
            'teacher_id' => NULL,
        ];

        $this->my_class->createSection($s);

        return Qs::jsonStoreOk();
    }

    public function edit($id)
    {
        $d['c'] = $c = $this->my_class->find($id);

        return is_null($c) ? Qs::goWithDanger('classes.index') : view('pages.support_team.classes.edit', $d) ;
    }

    public function update(ClassUpdate $req, $id)
    {
        $data = $req->only(['name', 'code']);
        $this->my_class->update($id, $data);

        return redirect()->route('classes.index')->with('flash_success', __('msg.update_ok'));
    }

    public function deleteSelected(Request $request)
    {
        $data = $request->validate([
            'class_ids' => 'required|array|min:1',
            'class_ids.*' => 'required|integer|distinct|exists:my_classes,id',
        ]);

        $deleted = 0;
        foreach ($data['class_ids'] as $classId) {
            $deleted += $this->my_class->delete($classId);
        }

        return redirect()->route('classes.index')->with('flash_success', $deleted.' class(es) deleted successfully.');
    }

    public function destroy($id)
    {
        $this->my_class->delete($id);
        return back()->with('flash_success', __('msg.del_ok'));
    }

}
