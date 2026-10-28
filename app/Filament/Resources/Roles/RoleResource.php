<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Support\TranslatesResourceLabels;
use App\Models\User;
use App\Support\Permissions;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use UnitEnum;

class RoleResource extends Resource
{
    use TranslatesResourceLabels;

    protected static ?string $model = Role::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Users & security';

    protected static ?string $navigationLabel = 'Roles & permissions';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view roles') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create roles') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return $record->name !== User::SUPER_ADMIN && (auth()->user()?->can('update roles') ?? false);
    }

    public static function canDelete(Model $record): bool
    {
        return $record->name !== User::SUPER_ADMIN && $record->users()->doesntExist() && (auth()->user()?->can('delete roles') ?? false);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(100)->alphaDash()->unique(ignoreRecord: true)->columnSpanFull(),
            TextInput::make('guard_name')->default('web')->hidden()->dehydrated(),
            Section::make(__('Permissions'))->description(__('Each module has view / create / update / delete.'))->columns(3)->schema(
                collect(Permissions::MODULES)->map(fn (string $module) => CheckboxList::make("permissions_{$module}")
                    ->label(str($module)->headline()->toString())
                    ->relationship('permissions', 'name', fn (Builder $query) => $query->where('name', 'like', "% {$module}"))
                    ->getOptionLabelFromRecordUsing(fn (Model $permission) => __(ucfirst(explode(' ', $permission->name)[0])))
                    ->bulkToggleable())->all()
            ),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['users', 'permissions']))
            ->columns([
                TextColumn::make('name')->badge(),
                TextColumn::make('permissions_count')->label('Permissions')->formatStateUsing(fn ($state, Role $record) => $record->name === User::SUPER_ADMIN ? __('All') : $state),
                TextColumn::make('users_count')->label('Users'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
