<?php

namespace App\Console\Commands;

use App\Models\LanguageLine;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:sync-translations')]
#[Description('Import UI strings from lang/ files into the database translation editor (never overwrites edits)')]
class SyncTranslationsCommand extends Command
{
    public function handle(): int
    {
        $created = LanguageLine::syncFromFiles();
        $this->components->info("Translation lines synced: {$created} new, ".LanguageLine::count().' total.');

        return self::SUCCESS;
    }
}
