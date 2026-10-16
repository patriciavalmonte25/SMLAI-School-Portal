<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Event;
use App\User;
use Tests\TestCase;

class CalendarAnnouncementSelectionTest extends TestCase
{
    private function makeAdministrator()
    {
        $username = 'administrator-' . uniqid();

        return User::create([
            'name' => 'List Manager',
            'username' => $username,
            'email' => $username . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'administrator',
            'code' => strtoupper($username),
        ]);
    }

    public function test_calendar_list_uses_selection_controls_and_compact_columns()
    {
        $manager = $this->makeAdministrator();
        Event::create([
            'date' => '2026-11-01',
            'event' => 'Calendar Selection Event',
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->get('http://localhost/events')
            ->assertOk()
            ->assertSee('Created On')
            ->assertSee('Visibility')
            ->assertDontSee('Created At')
            ->assertDontSee('<th>S/N</th>', false)
            ->assertSee('bulk-select-checkbox', false)
            ->assertSee('Delete Selected')
            ->assertSee('width: 105px', false)
            ->assertSee('created-on-column', false)
            ->assertSee('width: 130px', false)
            ->assertSee('icon-paragraph-justify3', false)
            ->assertSee('name="visibility[]"', false)
            ->assertSee("width: 52px", false);
    }

    public function test_announcement_list_uses_selection_controls_and_compact_columns()
    {
        $manager = $this->makeAdministrator();
        Announcement::create([
            'date' => '2026-11-02',
            'announcement' => 'Announcement Selection Item',
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->get('http://localhost/announcements')
            ->assertOk()
            ->assertSee('Created On')
            ->assertSee('Visibility')
            ->assertDontSee('Created At')
            ->assertDontSee('<th>S/N</th>', false)
            ->assertSee('bulk-select-checkbox', false)
            ->assertSee('Delete Selected')
            ->assertSee('width: 105px', false)
            ->assertSee('created-on-column', false)
            ->assertSee('width: 130px', false)
            ->assertSee('icon-paragraph-justify3', false)
            ->assertSee('name="visibility[]"', false)
            ->assertSee("width: 52px", false);
    }

    public function test_calendar_and_announcement_forms_default_visibility_to_all_non_admin_types()
    {
        $manager = $this->makeAdministrator();
        $checkboxes = [
            'registrar', 'accountant', 'teacher', 'student', 'parent',
        ];

        $eventResponse = $this->actingAs($manager)->get('http://localhost/events')->assertOk();
        $announcementResponse = $this->get('http://localhost/announcements')->assertOk();
        foreach ($checkboxes as $type) {
            $checkedOption = 'name="visibility[]" value="' . $type . '" checked';
            $eventResponse->assertSee($checkedOption, false);
            $announcementResponse->assertSee($checkedOption, false);
        }
        $eventResponse->assertDontSee('name="visibility[]" value="administrator"', false);
        $announcementResponse->assertDontSee('name="visibility[]" value="administrator"', false);
    }

    public function test_dashboard_visibility_is_filtered_by_user_type()
    {
        $manager = $this->makeAdministrator();
        $teacherEvent = Event::create([
            'date' => '2026-11-18',
            'event' => 'Teacher-only Event ' . uniqid(),
            'created_by' => $manager->id,
            'visibility' => ['teacher'],
        ]);
        $parentAnnouncement = Announcement::create([
            'date' => '2026-11-19',
            'announcement' => 'Parent-only Announcement ' . uniqid(),
            'created_by' => $manager->id,
            'visibility' => ['parent'],
        ]);
        $administratorAnnouncement = Announcement::create([
            'date' => '2026-11-20',
            'announcement' => 'Administrator always sees this ' . uniqid(),
            'created_by' => $manager->id,
            'visibility' => ['teacher'],
        ]);
        $hiddenAnnouncement = Announcement::create([
            'date' => '2026-11-21',
            'announcement' => 'Hidden announcement ' . uniqid(),
            'created_by' => $manager->id,
            'visibility' => [],
        ]);
        $hiddenEvent = Event::create([
            'date' => '2026-11-22',
            'event' => 'Hidden event ' . uniqid(),
            'created_by' => $manager->id,
            'visibility' => [],
        ]);
        $teacher = $this->makeUser('teacher');
        $parent = $this->makeUser('parent');

        $this->actingAs($manager)
            ->get('http://localhost/dashboard')
            ->assertOk()
            ->assertSee($administratorAnnouncement->announcement);

        $this->actingAs($teacher)
            ->get('http://localhost/dashboard')
            ->assertOk()
            ->assertSee($teacherEvent->event)
            ->assertDontSee($parentAnnouncement->announcement)
            ->assertDontSee($hiddenAnnouncement->announcement)
            ->assertDontSee($hiddenEvent->event);

        $this->actingAs($parent)
            ->get('http://localhost/dashboard')
            ->assertOk()
            ->assertSee($parentAnnouncement->announcement)
            ->assertDontSee($teacherEvent->event)
            ->assertDontSee($hiddenAnnouncement->announcement)
            ->assertDontSee($hiddenEvent->event);

        $this->actingAs($manager)
            ->get('http://localhost/dashboard')
            ->assertOk()
            ->assertSee($administratorAnnouncement->announcement);
    }

    public function test_create_and_update_persist_visibility_selections()
    {
        $manager = $this->makeAdministrator();
        $eventTitle = 'Visibility Create Event ' . uniqid();
        $this->actingAs($manager)
            ->post('http://localhost/events', [
                'date' => '2026-11-20',
                'event' => $eventTitle,
                'visibility_present' => '1',
                'visibility' => ['teacher', 'parent'],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $event = Event::where('event', $eventTitle)->firstOrFail();
        $this->assertSame(['teacher', 'parent'], $event->visibility);

        $this->actingAs($manager)
            ->get('http://localhost/events/edit/' . \App\Helpers\Qs::hash($event->id))
            ->assertOk()
            ->assertSee('Edit Event')
            ->assertSee('Submit Changes')
            ->assertSee($eventTitle);

        $this->put('http://localhost/events/' . \App\Helpers\Qs::hash($event->id), [
            'date' => '2026-11-20',
            'event' => $eventTitle,
            'visibility_present' => '1',
        ])->assertRedirect(route('events.index'));
        $this->assertSame([], $event->fresh()->visibility);

        $this->post('http://localhost/announcements', [
            'date' => '2026-11-21',
            'announcement' => 'Visibility Create Announcement',
            'visibility_present' => '1',
            'visibility' => ['student'],
        ])->assertOk()->assertJsonPath('ok', true);
        $announcement = Announcement::where('announcement', 'Visibility Create Announcement')->firstOrFail();
        $this->assertSame(['student'], $announcement->visibility);

        $this->actingAs($manager)
            ->get('http://localhost/announcements/edit/' . \App\Helpers\Qs::hash($announcement->id))
            ->assertOk()
            ->assertSee('Edit Announcement')
            ->assertSee('Submit Changes')
            ->assertSee($announcement->announcement);

        $this->put('http://localhost/announcements/' . \App\Helpers\Qs::hash($announcement->id), [
            'date' => '2026-11-21',
            'announcement' => 'Updated Visibility Announcement',
            'visibility_present' => '1',
            'visibility' => ['registrar'],
        ])->assertRedirect(route('announcements.index'));
        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'announcement' => 'Updated Visibility Announcement',
        ]);
    }

    public function test_bulk_deletion_removes_only_selected_events_and_announcements()
    {
        $manager = $this->makeAdministrator();
        $selectedEvent = Event::create(['date' => '2026-11-03', 'event' => 'Selected event']);
        $remainingEvent = Event::create(['date' => '2026-11-04', 'event' => 'Remaining event']);
        $selectedAnnouncement = Announcement::create(['date' => '2026-11-05', 'announcement' => 'Selected announcement']);
        $remainingAnnouncement = Announcement::create(['date' => '2026-11-06', 'announcement' => 'Remaining announcement']);

        $this->actingAs($manager)
            ->post('http://localhost/events/delete-selected', ['event_ids' => [$selectedEvent->id]])
            ->assertRedirect('http://localhost/events');
        $this->assertDatabaseMissing('events', ['id' => $selectedEvent->id]);
        $this->assertDatabaseHas('events', ['id' => $remainingEvent->id]);

        $this->post('http://localhost/announcements/delete-selected', ['announcement_ids' => [$selectedAnnouncement->id]])
            ->assertRedirect('http://localhost/announcements');
        $this->assertDatabaseMissing('announcements', ['id' => $selectedAnnouncement->id]);
        $this->assertDatabaseHas('announcements', ['id' => $remainingAnnouncement->id]);
    }

    public function test_calendar_event_popup_has_a_top_right_date_and_larger_description_text()
    {
        $manager = $this->makeAdministrator();
        Event::create([
            'date' => '2026-11-17',
            'event' => 'Calendar Popup Example',
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->get('http://localhost/dashboard')
            ->assertOk()
            ->assertSee('eventModalDate', false)
            ->assertSee('event-modal-header', false)
            ->assertSee('11/17/2026')
            ->assertSee('font-size: 18px', false);
    }

    private function makeUser($type)
    {
        $username = $type . '-' . uniqid();

        return User::create([
            'name' => ucfirst($type) . ' Visibility User',
            'username' => $username,
            'email' => $username . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => $type,
            'code' => strtoupper($username),
        ]);
    }
}
