<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Support\TranslatesResourceLabels;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use UnitEnum;

class UserResource extends Resource
{
    use TranslatesResourceLabels;

    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Users & security';

    protected static ?string $navigationLabel = 'Administrators';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('email')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->rule(PasswordRule::min(12)->letters()->numbers())
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->helperText(fn (string $operation) => __($operation === 'edit' ? 'Leave empty to keep the current password.' : 'At least 12 characters with letters and numbers.')),
                Toggle::make('is_active')
                    ->label('Account active')
                    ->default(true)
                    ->disabled(fn (?Model $record) => $record?->is(auth()->user()))
                    ->helperText(__('Inactive accounts cannot sign in to the dashboard.')),
                CheckboxList::make('roles')
                    ->relationship('roles', 'name', fn (Builder $query) => auth()->user()?->hasRole(User::SUPER_ADMIN) ? $query : $query->where('name', '!=', User::SUPER_ADMIN))
                    ->columns(3)
                    ->required()
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('roles'))
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('roles.name')->badge(),
                ToggleColumn::make('is_active')->label('Active')->disabled(fn (User $record) => $record->is(auth()->user())),
                TextColumn::make('last_login_at')->label('Last sign-in')->since()->placeholder(__('Never'))->sortable(),
            ])
            ->filters([SelectFilter::make('roles')->relationship('roles', 'name')])
            ->recordActions([
                Action::make('sendReset')
                    ->label('Send password reset')
                    ->icon('heroicon-o-key')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $status = Password::broker()->sendResetLink(['email' => $record->email]);
                        Notification::make()->title(__($status))->{$status === Password::RESET_LINK_SENT ? 'success' : 'warning'}()->send();
                    }),
                EditAction::make(),
                DeleteAction::make()->hidden(fn (User $record) => $record->is(auth()->user())),
            ]);
    }

    /**
     * Only super-admins may edit other super-admins.
     */
    public static function canEdit(Model $record): bool
    {
        return parent::canEdit($record) && (! $record->hasRole(User::SUPER_ADMIN) || auth()->user()?->hasRole(User::SUPER_ADMIN));
    }

    public static function canDelete(Model $record): bool
    {
        return self::canEdit($record) && ! $record->is(auth()->user());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
