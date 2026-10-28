<?php

namespace Tests\Feature;

use App\Enums\InboxStatus;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Filament\Resources\JobApplications\Pages\ViewJobApplication;
use App\Filament\Support\Inbox;
use App\Models\ContactMessage;
use App\Models\JobApplication;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\AdminTestCase;

class InboxAdminTest extends AdminTestCase
{
    public function test_inbox_role_works_messages_but_cannot_edit_content(): void
    {
        $message = ContactMessage::create(['name' => 'Jane', 'email' => 'jane@example.com', 'message' => 'Prices?', 'locale' => 'en']);
        $this->signIn('inbox');

        $this->get(route('filament.admin.resources.contact-messages.index'))->assertOk()->assertSee('Jane');
        $this->get(route('filament.admin.resources.job-applications.index'))->assertOk();
        $this->get(route('filament.admin.resources.products.index'))->assertForbidden();

        $this->signIn('editor');
        $this->get(route('filament.admin.resources.contact-messages.view', $message))->assertForbidden();
    }

    public function test_opening_a_message_marks_it_read_and_it_can_be_marked_unread(): void
    {
        $message = ContactMessage::create(['name' => 'Jane', 'email' => 'jane@example.com', 'message' => 'Prices?']);

        Livewire::test(ViewContactMessage::class, ['record' => $message->getRouteKey()])
            ->assertSee('Prices?');
        $this->assertNotNull($message->fresh()->read_at);

        Livewire::test(ViewContactMessage::class, ['record' => $message->getRouteKey()])
            ->callAction('toggleRead');
        $this->assertNull($message->fresh()->read_at);
    }

    public function test_status_and_internal_notes_are_saved(): void
    {
        $message = ContactMessage::create(['name' => 'Jane', 'email' => 'jane@example.com']);

        Livewire::test(ListContactMessages::class)
            ->callAction(TestAction::make('edit')->table($message), ['status' => InboxStatus::Replied->value, 'notes' => 'Sent the price list.'])
            ->assertHasNoFormErrors();

        $message->refresh();
        $this->assertSame(InboxStatus::Replied, $message->status);
        $this->assertSame('Sent the price list.', $message->notes);
    }

    public function test_messages_are_filtered_and_marked_in_bulk(): void
    {
        $new = ContactMessage::create(['name' => 'New', 'email' => 'a@example.com']);
        $archived = ContactMessage::create(['name' => 'Old', 'email' => 'b@example.com', 'status' => InboxStatus::Archived]);

        Livewire::test(ListContactMessages::class)
            ->filterTable('status', InboxStatus::Archived->value)
            ->assertCanSeeTableRecords([$archived])
            ->assertCanNotSeeTableRecords([$new]);

        Livewire::test(ListContactMessages::class)
            ->selectTableRecords([$new, $archived])
            ->callAction(TestAction::make('markRead')->table()->bulk());

        $this->assertSame(0, ContactMessage::unread()->count());
    }

    public function test_csv_export_neutralises_spreadsheet_formulas(): void
    {
        $message = ContactMessage::create(['name' => '=HYPERLINK("http://evil.test")', 'email' => 'x@example.com', 'message' => '+cmd']);

        $response = Inbox::export([$message], ['name' => 'Name', 'message' => 'Message'], 'messages');
        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString("'+cmd", $csv);

        Livewire::test(ListContactMessages::class)
            ->selectTableRecords([$message])
            ->callAction(TestAction::make('export')->table()->bulk())
            ->assertFileDownloaded('contact-messages-'.now()->format('Y-m-d').'.csv');
    }

    public function test_cv_of_a_job_application_is_downloaded_from_private_storage(): void
    {
        $path = UploadedFile::fake()->create('cv.pdf', 20, 'application/pdf')->store('applications', 'local');
        $application = JobApplication::create([
            'name' => 'Omar', 'email' => 'omar@example.com', 'phone' => '0100', 'job_title' => 'QA',
            'cv_path' => $path, 'cv_original_name' => 'Omar CV.pdf',
        ]);

        Livewire::test(ViewJobApplication::class, ['record' => $application->getRouteKey()])
            ->assertSee('QA')
            ->callAction('downloadCv')
            ->assertFileDownloaded('Omar CV.pdf');

        $this->get('/storage/'.$path)->assertForbidden(); // private disk: only signed URLs are served
    }
}
