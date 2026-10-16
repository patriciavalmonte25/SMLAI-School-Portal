<?php

namespace App\Http\Controllers;

use App\Helpers\Qs;
use App\Helpers\ContentVisibility;
use App\Models\Announcement;
use App\Models\Event;
use App\Models\Setting;
use App\Repositories\UserRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    protected $user;
    public function __construct(UserRepo $user)
    {
        $this->user = $user;
    }


    public function index()
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->user_type === 'enroll' ? 'pending_submissions' : 'dashboard');
        }

        return redirect()->route('public_home');
    }

    public function privacy_policy()
    {
        $data['app_name'] = config('app.name');
        $data['app_url'] = config('app.url');
        $data['contact_phone'] = Qs::getSetting('phone');
        return view('pages.other.privacy_policy', $data);
    }

    public function terms_of_use()
    {
        $data['app_name'] = config('app.name');
        $data['app_url'] = config('app.url');
        $data['contact_phone'] = Qs::getSetting('phone');
        return view('pages.other.terms_of_use', $data);
    }

    public function public_home()
    {
        $data = $this->getHomePageData();
        return view('pages.other.home_page', $data);
    }

    public function enrollment()
    {
        return view('pages.other.enrollment_form');
    }

    public function edit_home_page()
    {
        $data = $this->getHomePageData();
        return view('pages.super_admin.home_page', $data);
    }

    public function update_home_page(Request $request)
    {
        $data = $request->except('_token', '_method');

        $imageFields = [
            'home_page_logo' => 'home_page_logo',
            'home_page_background' => 'home_page_background',
            'home_page_login_background' => 'home_page_login_background',
            'home_page_service_pre_elementary_image' => 'home_page_service_pre_elementary_image',
            'home_page_service_elementary_image' => 'home_page_service_elementary_image',
            'home_page_service_junior_high_image' => 'home_page_service_junior_high_image',
            'home_page_service_senior_high_image' => 'home_page_service_senior_high_image',
            'home_page_pathway_accountancy_image' => 'home_page_pathway_accountancy_image',
            'home_page_pathway_architecture_image' => 'home_page_pathway_architecture_image',
            'home_page_pathway_arts_image' => 'home_page_pathway_arts_image',
            'home_page_pathway_healthcare_image' => 'home_page_pathway_healthcare_image',
        ];

        foreach ($imageFields as $inputName => $settingKey) {
            if ($request->hasFile($inputName)) {
                $path = $request->file($inputName)->store('uploads/home', 'public');
                $data[$settingKey] = asset('storage/'.$path);
            } elseif (!array_key_exists($settingKey, $data)) {
                $data[$settingKey] = $this->getHomePageData()[$settingKey] ?? '';
            }
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }

            Setting::updateOrCreate(
                ['type' => $key],
                ['description' => (string) $value]
            );
        }

        return back()->with('flash_success', __('msg.update_ok'));
    }

    public function dashboard()
    {
        if (Auth::check() && Auth::user()->user_type === 'enroll') {
            return redirect()->route('pending_submissions');
        }

        $d=[];

        // Get announcements where date is today or in the future, ordered by date ascending (closest dates first)
        $today = date('Y-m-d');
        $d['announcements'] = Announcement::where('date', '>=', $today)
            ->orderBy('date', 'asc')
            ->get()
            ->filter(function ($announcement) {
                if (in_array(Auth::user()->user_type, ['administrator', 'admin'], true)) {
                    return true;
                }

                $visibility = $announcement->visibility === null
                    ? ContentVisibility::defaults()
                    : $announcement->visibility;

                return ContentVisibility::allows($visibility, Auth::user()->user_type);
            })->values();

        // Get all events for calendar
        $d['events'] = Event::all()->filter(function ($event) {
            $visibility = $event->visibility === null
                ? ContentVisibility::defaults()
                : $event->visibility;

            return ContentVisibility::allows($visibility, Auth::user()->user_type);
        })->values();

        return view('pages.support_team.dashboard', $d);
    }

    protected function getHomePageData()
    {
        $defaults = [
            'home_page_school_name' => config('app.name', 'School Management System'),
            'home_page_logo' => '',
            'home_page_background' => '',
            'home_page_login_background' => '',
            'home_page_summary' => 'A vibrant learning community dedicated to academic excellence, character building, and holistic growth.',
            'home_page_vision' => 'To be a leading institution that nurtures globally competitive, value-driven, and lifelong learners.',
            'home_page_mission' => 'To provide quality education through innovation, discipline, and compassionate service.',
            'home_page_resource_facebook' => '#',
            'home_page_resource_genyo' => '#',
            'home_page_resource_phone' => '',
            'home_page_resource_telephone' => '',
            'home_page_resource_email' => '',
            'home_page_service_pre_elementary_title' => 'Pre-Elementary',
            'home_page_service_pre_elementary_grade' => 'Nursery to Kindergarten',
            'home_page_service_pre_elementary_summary' => 'A nurturing environment that helps young learners build confidence, curiosity, and foundational skills.',
            'home_page_service_pre_elementary_image' => '',
            'home_page_service_elementary_title' => 'Elementary',
            'home_page_service_elementary_grade' => 'Grade 1 to 6',
            'home_page_service_elementary_summary' => 'A strong foundational program that develops literacy, numeracy, and social skills for young learners.',
            'home_page_service_elementary_image' => '',
            'home_page_service_junior_high_title' => 'Junior High School',
            'home_page_service_junior_high_grade' => 'Grade 7 to 10',
            'home_page_service_junior_high_summary' => 'A well-rounded academic experience that encourages critical thinking, discipline, and leadership.',
            'home_page_service_junior_high_image' => '',
            'home_page_service_senior_high_title' => 'Senior High School',
            'home_page_service_senior_high_grade' => 'Grade 11 to 12',
            'home_page_service_senior_high_summary' => 'Specialized learning pathways designed to prepare students for college, entrepreneurship, and technical careers.',
            'home_page_service_senior_high_image' => '',
            'home_page_pathway_accountancy_title' => 'Accountancy and Entrepreneurship',
            'home_page_pathway_accountancy_summary' => 'Practical training in business, finance, and enterprise development.',
            'home_page_pathway_accountancy_image' => '',
            'home_page_pathway_architecture_title' => 'Architecture and Engineering',
            'home_page_pathway_architecture_summary' => 'Hands-on preparation for design, planning, and technical problem solving.',
            'home_page_pathway_architecture_image' => '',
            'home_page_pathway_arts_title' => 'Arts and Social Sciences',
            'home_page_pathway_arts_summary' => 'A creative and analytical pathway centered on communication, culture, and community.',
            'home_page_pathway_arts_image' => '',
            'home_page_pathway_healthcare_title' => 'Healthcare and Safety',
            'home_page_pathway_healthcare_summary' => 'Skills-based learning focused on wellness, care, and public safety standards.',
            'home_page_pathway_healthcare_image' => '',
        ];

        $settings = Setting::whereIn('type', array_keys($defaults))->pluck('description', 'type')->toArray();

        $data = array_replace($defaults, $settings);
        foreach (array_keys($defaults) as $key) {
            if (substr($key, -6) === '_image' || in_array($key, ['home_page_logo', 'home_page_background', 'home_page_login_background'], true)) {
                $data[$key] = $this->normalizeImageUrl($data[$key]);
            }
        }

        return $data;
    }

    protected function normalizeImageUrl($value)
    {
        if (!$value) {
            return $value;
        }

        $path = parse_url($value, PHP_URL_PATH) ?: $value;
        $storagePosition = strpos($path, '/storage/');
        if ($storagePosition !== false) {
            return asset(ltrim(substr($path, $storagePosition + 1), '/'));
        }

        return $value;
    }
}
