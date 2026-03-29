<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class FileManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.file-manager';
}
