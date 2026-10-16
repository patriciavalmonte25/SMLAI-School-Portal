<?php

Auth::routes();

//Route::get('/test', 'TestController@index')->name('test');
Route::get('/privacy-policy', 'HomeController@privacy_policy')->name('privacy_policy');
Route::get('/terms-of-use', 'HomeController@terms_of_use')->name('terms_of_use');
Route::get('/', 'HomeController@public_home')->name('public_home');
Route::get('/enrollment-form', 'HomeController@enrollment')->name('enrollment_form');
Route::post('/enrollment-form', 'EnrollmentController@store')->name('enrollment_form.store');


Route::group(['middleware' => 'auth'], function () {

    Route::get('/home', 'HomeController@dashboard')->name('home');
    Route::get('/dashboard', 'HomeController@dashboard')->name('dashboard');
    Route::get('/my-class', 'SuperAdmin\ClassStudentController@myClass')->name('student.my_class');
    Route::get('/inbox', 'EnrollmentController@accountInbox')->name('account_inbox');
    Route::post('/inbox/account-creation-notices/{recipient_id}/acknowledge', 'EnrollmentController@acknowledgeParentAccountCreationNotice')->name('account_inbox.account_creation_notices.acknowledge');
    Route::get('/pending-submissions', 'EnrollmentController@pendingSubmissions')->name('pending_submissions');
    Route::post('/pending-submissions/acknowledge/{enrollment_id}', 'EnrollmentController@acknowledgeDenial')->name('pending_submissions.acknowledge');
    Route::post('/pending-submissions/account-created-acknowledge/{enrollment_id}', 'EnrollmentController@acknowledgeAccountCreatedNotice')->name('pending_submissions.account_created_acknowledge');
    Route::post('/pending-submissions/account-notice-acknowledge/{notice_id}', 'EnrollmentController@acknowledgeAccountCreationNotice')->name('pending_submissions.account_notice_acknowledge');
    Route::get('/document-requests', 'DocumentRequestController@index')->name('document_requests.index');
    Route::post('/document-requests', 'DocumentRequestController@store')->name('document_requests.store');
    Route::get('/accountant/document-requests', 'DocumentRequestController@accountantIndex')->name('accountant.document_requests.index');
    Route::get('/accountant/document-requests/{request_id}', 'DocumentRequestController@show')->name('accountant.document_requests.show');
    Route::post('/accountant/document-requests/{request_id}/approve', 'DocumentRequestController@approve')->name('accountant.document_requests.approve');
    Route::post('/accountant/document-requests/{request_id}/reject', 'DocumentRequestController@reject')->name('accountant.document_requests.reject');
    Route::post('/accountant/document-requests/{request_id}/collect', 'DocumentRequestController@collect')->name('accountant.document_requests.collect');

    Route::group(['prefix' => 'my_account'], function() {
        Route::get('/', 'MyAccountController@edit_profile')->name('my_account');
        Route::put('/', 'MyAccountController@update_profile')->name('my_account.update');
        Route::put('/change_password', 'MyAccountController@change_pass')->name('my_account.change_pass');
    });

    /*************** Support Team *****************/
    Route::group(['namespace' => 'SupportTeam',], function(){

        /*************** Students *****************/
        Route::group(['prefix' => 'students'], function(){
            Route::get('{student_id}/grades', 'StudentGradeController@show')->name('students.grades.show');
            Route::get('{student_id}/grades/edit', 'StudentGradeController@edit')->name('students.grades.edit');
            Route::post('{student_id}/grades/layout', 'StudentGradeController@saveLayout')->name('students.grades.save_layout');
            Route::post('{student_id}/grades/visibility', 'StudentGradeController@saveVisibility')->name('students.grades.save_visibility');
            Route::get('reset_pass/{st_id}', 'StudentRecordController@reset_pass')->name('st.reset_pass');
            Route::get('graduated', 'StudentRecordController@graduated')->name('students.graduated');
            Route::put('not_graduated/{id}', 'StudentRecordController@not_graduated')->name('st.not_graduated');
            Route::get('list/{class_id}', [\App\Http\Controllers\SuperAdmin\ClassStudentController::class, 'show'])->name('students.list')->middleware('teamSAT');

            /* Promotions */
            Route::post('promote_selector', 'PromotionController@selector')->name('students.promote_selector');
            Route::get('promotion/manage', 'PromotionController@manage')->name('students.promotion_manage');
            Route::delete('promotion/reset/{pid}', 'PromotionController@reset')->name('students.promotion_reset');
            Route::delete('promotion/reset_all', 'PromotionController@reset_all')->name('students.promotion_reset_all');
            Route::get('promotion/{fc?}/{fs?}/{tc?}/{ts?}', 'PromotionController@promotion')->name('students.promotion');
            Route::post('promote/{fc}/{fs}/{tc}/{ts}', 'PromotionController@promote')->name('students.promote');

        });

        /*************** Users *****************/
        Route::group(['prefix' => 'users'], function(){
            Route::get('reset_pass/{id}', 'UserController@reset_pass')->name('users.reset_pass');
            Route::post('delete-selected', 'UserController@deleteSelected')->name('users.delete_selected');
        });

        /*************** School Calendar Events *****************/
        Route::group(['prefix' => 'events'], function(){
            Route::get('/', 'EventController@index')->name('events.index');
            Route::post('/', 'EventController@store')->name('events.store');
            Route::post('delete-selected', 'EventController@deleteSelected')->name('events.delete_selected');
            Route::get('edit/{id}', 'EventController@edit')->name('events.edit');
            Route::put('/{id}', 'EventController@update')->name('events.update');
            Route::delete('/{id}', 'EventController@delete')->name('events.delete');
        });

        /*************** Announcements *****************/
        Route::group(['prefix' => 'announcements'], function(){
            Route::get('/', 'AnnouncementController@index')->name('announcements.index');
            Route::post('/', 'AnnouncementController@store')->name('announcements.store');
            Route::post('delete-selected', 'AnnouncementController@deleteSelected')->name('announcements.delete_selected');
            Route::get('edit/{id}', 'AnnouncementController@edit')->name('announcements.edit');
            Route::put('/{id}', 'AnnouncementController@update')->name('announcements.update');
            Route::delete('/{id}', 'AnnouncementController@delete')->name('announcements.delete');
        });

        /*************** TimeTables *****************/
        Route::group(['prefix' => 'timetables'], function(){
            Route::get('/', 'TimeTableController@index')->name('tt.index');

            Route::group(['middleware' => 'teamSAT'], function() {
                Route::post('/', 'TimeTableController@store')->name('tt.store');
                Route::put('/{tt}', 'TimeTableController@update')->name('tt.update');
                Route::delete('/{tt}', 'TimeTableController@delete')->name('tt.delete');
            });

            /*************** TimeTable Records *****************/
            Route::group(['prefix' => 'records'], function(){

                Route::group(['middleware' => 'teamSAT'], function(){
                    Route::get('manage/{ttr}', 'TimeTableController@manage')->name('ttr.manage');
                    Route::post('/', 'TimeTableController@store_record')->name('ttr.store');
                    Route::post('layout/{ttr}', 'TimeTableController@save_layout')->name('ttr.layout.save');
                    Route::get('edit/{ttr}', 'TimeTableController@edit_record')->name('ttr.edit');
                    Route::put('/{ttr}', 'TimeTableController@update_record')->name('ttr.update');
                });

                Route::delete('/{ttr}', 'TimeTableController@delete_record')->name('ttr.destroy')->middleware('teamSAT');

                Route::get('show/{ttr}', 'TimeTableController@show_record')->name('ttr.show');
                Route::get('print/{ttr}', 'TimeTableController@print_record')->name('ttr.print');

            });

            /*************** Time Slots *****************/
            Route::group(['prefix' => 'time_slots', 'middleware' => 'teamSAT'], function(){
                Route::post('/', 'TimeTableController@store_time_slot')->name('ts.store');
                Route::post('/use/{ttr}', 'TimeTableController@use_time_slot')->name('ts.use');
                Route::get('edit/{ts}', 'TimeTableController@edit_time_slot')->name('ts.edit');
                Route::delete('/{ts}', 'TimeTableController@delete_time_slot')->name('ts.destroy');
                Route::put('/{ts}', 'TimeTableController@update_time_slot')->name('ts.update');
            });

        });

        /*************** Payments *****************/
        Route::group(['prefix' => 'payments'], function(){

            Route::get('manage/{class_id?}', 'PaymentController@manage')->name('payments.manage');
            Route::get('invoice/{id}/{year?}', 'PaymentController@invoice')->name('payments.invoice');
            Route::get('receipts/{id}', 'PaymentController@receipts')->name('payments.receipts');
            Route::get('pdf_receipts/{id}', 'PaymentController@pdf_receipts')->name('payments.pdf_receipts');
            Route::post('select_year', 'PaymentController@select_year')->name('payments.select_year');
            Route::post('select_class', 'PaymentController@select_class')->name('payments.select_class');
            Route::delete('reset_record/{id}', 'PaymentController@reset_record')->name('payments.reset_record');
            Route::post('pay_now/{id}', 'PaymentController@pay_now')->name('payments.pay_now');
        });

        /*************** Pins *****************/
        Route::group(['prefix' => 'pins'], function(){
            Route::get('create', 'PinController@create')->name('pins.create');
            Route::get('/', 'PinController@index')->name('pins.index');
            Route::post('/', 'PinController@store')->name('pins.store');
            Route::get('enter/{id}', 'PinController@enter_pin')->name('pins.enter');
            Route::post('verify/{id}', 'PinController@verify')->name('pins.verify');
            Route::delete('/', 'PinController@destroy')->name('pins.destroy');
        });

        Route::resource('students', 'StudentRecordController');
        Route::resource('users', 'UserController');
        Route::post('classes/delete-selected', 'MyClassController@deleteSelected')->name('classes.delete_selected');
        Route::resource('classes', 'MyClassController');
        Route::resource('sections', 'SectionController');
        Route::resource('subjects', 'SubjectController');
        Route::resource('payments', 'PaymentController');

    });

    /************************ AJAX ****************************/
    Route::group(['prefix' => 'ajax'], function() {
        Route::get('get_lga/{state_id}', 'AjaxController@get_lga')->name('get_lga');
        Route::get('get_class_sections/{class_id}', 'AjaxController@get_class_sections')->name('get_class_sections');
        Route::get('get_class_subjects/{class_id}', 'AjaxController@get_class_subjects')->name('get_class_subjects');
        Route::get('get_class_students/{class_id}', 'AjaxController@get_class_students')->name('get_class_students');
    });

});

/************************ SUPER ADMIN ****************************/
Route::group(['namespace' => 'SuperAdmin','middleware' => 'administrator', 'prefix' => 'super_admin'], function(){

    Route::get('/settings', 'SettingController@index')->name('settings');
    Route::put('/settings', 'SettingController@update')->name('settings.update');

});

Route::group(['namespace' => 'SuperAdmin', 'middleware' => 'administratorOrRegistrar', 'prefix' => 'super_admin'], function(){
    Route::get('/classes-students/{class_id}', 'ClassStudentController@show')->name('super_admin.classes_students.show');
    Route::get('/school-statistics/{type?}', 'SchoolStatisticsController@index')->name('super_admin.school_statistics');
});

Route::group(['namespace' => 'SuperAdmin', 'middleware' => 'registrar', 'prefix' => 'registrar'], function(){
    Route::group(['prefix' => 'students'], function(){
        Route::get('enroll', 'StudentEnrollmentController@create')->name('registrar.students.enroll');
        Route::post('enroll', 'StudentEnrollmentController@store')->name('registrar.students.enroll.store');
        Route::get('inbox', 'EnrollmentReviewController@index')->name('registrar.students.inbox');
        Route::get('forms/{enrollment_id}', 'EnrollmentReviewController@show')->name('registrar.students.view_form');
        Route::post('delete-selected', 'EnrollmentReviewController@deleteSelected')->name('registrar.students.delete_selected');
        Route::post('{enrollment_id}/approve', 'EnrollmentReviewController@approve')->name('registrar.students.approve');
        Route::post('{enrollment_id}/deny', 'EnrollmentReviewController@deny')->name('registrar.students.deny');
        Route::post('{enrollment_id}/mark-enrolled', 'EnrollmentReviewController@markEnrolled')->name('registrar.students.mark_enrolled');
        Route::get('outbox', 'OutboxController@index')->name('registrar.students.outbox');
        Route::post('outbox', 'OutboxController@store')->name('registrar.students.outbox.store');
        Route::post('outbox/delete-selected', 'OutboxController@deleteSelected')->name('registrar.students.outbox.delete_selected');
    });
});

Route::group(['namespace' => 'SupportTeam', 'middleware' => 'registrar', 'prefix' => 'registrar'], function(){
    Route::get('students', 'StudentRecordController@registrarIndex')->name('registrar.students.index');
    Route::post('students/delete-users', 'StudentRecordController@deleteSelectedStudents')->name('registrar.students.delete_users');
});

Route::group(['namespace' => 'SuperAdmin', 'middleware' => 'accountant', 'prefix' => 'accountant/billings'], function(){
    Route::get('/', 'BillingController@index')->name('accountant.billings.index');
    Route::get('/list/{grade_key}', 'BillingController@showList')->name('accountant.billings.list');
    Route::post('/list/{grade_key}/save', 'BillingController@saveList')->name('accountant.billings.save_list');
});

Route::group(['namespace' => 'SuperAdmin', 'middleware' => 'accountant', 'prefix' => 'accountant/notices'], function(){
    Route::get('/', 'BillingNoticeController@index')->name('accountant.notices.index');
    Route::get('/classes/{class_id}/students', 'BillingNoticeController@students')->name('accountant.notices.students');
    Route::post('/', 'BillingNoticeController@store')->name('accountant.notices.store');
    Route::post('/delete-selected', 'BillingNoticeController@deleteSelected')->name('accountant.notices.delete_selected');
    Route::post('/{notice_id}/mark-paid', 'BillingNoticeController@markPaid')->name('accountant.notices.mark_paid');
});

Route::group(['middleware' => 'auth'], function () {
    Route::get('/edit-home-page', 'HomeController@edit_home_page')->name('edit_home_page');
    Route::put('/edit-home-page', 'HomeController@update_home_page')->name('edit_home_page.update');
});

/************************ PARENT ****************************/
Route::group(['namespace' => 'MyParent','middleware' => 'my_parent',], function(){

    Route::get('/my_children', 'MyController@children')->name('my_children');
    Route::get('/parent-enrollment', 'MyController@enrollment')->name('parent_enrollment');

});
