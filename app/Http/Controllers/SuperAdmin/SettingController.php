<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Http\Requests\SettingUpdate;
use App\Repositories\MyClassRepo;
use App\Repositories\SettingRepo;

class SettingController extends Controller
{
    protected $setting, $my_class;

    public function __construct(SettingRepo $setting, MyClassRepo $my_class)
    {
        $this->setting = $setting;
        $this->my_class = $my_class;
    }

    public function index()
    {
         $s = $this->setting->all();
         $d['class_types'] = $this->my_class->getTypes();

         $settings = $s->mapWithKeys(function ($setting) {
             return [$setting->type => $setting->description];
         })->toArray();

         $defaults = [
             'system_name' => '',
             'system_title' => '',
             'phone' => '',
             'system_email' => '',
             'address' => '',
             'lock_exam' => 0,
             'logo' => '',
         ];

         $settings = array_replace($defaults, $settings);

         foreach ($d['class_types'] as $ct) {
             $feeKey = 'next_term_fees_' . strtolower($ct->code);
             if (!array_key_exists($feeKey, $settings)) {
                 $settings[$feeKey] = '';
             }
         }

         $d['s'] = $settings;
         return view('pages.super_admin.settings', $d);
    }

    public function update(SettingUpdate $req)
    {
        $sets = $req->except('_token', '_method', 'logo');
        $sets['lock_exam'] = $sets['lock_exam'] == 1 ? 1 : 0;
        $keys = array_keys($sets);
        $values = array_values($sets);
        for($i=0; $i<count($sets); $i++){
            $this->setting->update($keys[$i], $values[$i]);
        }

        if($req->hasFile('logo')) {
            $logo = $req->file('logo');
            $f = Qs::getFileMetaData($logo);
            $f['name'] = 'logo.' . $f['ext'];
            $f['path'] = $logo->storeAs(Qs::getPublicUploadPath(), $f['name']);
            $logo_path = asset('storage/' . $f['path']);
            $this->setting->update('logo', $logo_path);
        }

        return back()->with('flash_success', __('msg.update_ok'));

    }
}
