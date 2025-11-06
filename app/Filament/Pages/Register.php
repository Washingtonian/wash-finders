<?php

namespace App\Filament\Pages;

use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

class Register extends BaseRegister
{
    public function form(Schema $schema): Schema
    {
        return parent::form($schema)
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getRegistrationCodeFormComponent(),
            ]);
    }

    protected function getRegistrationCodeFormComponent(): Component
    {
        return \Filament\Forms\Components\TextInput::make('registration_code')
            ->label('Registration Code')
            ->password()
            ->required()
            ->helperText('Enter the secret registration code to create an account')
            ->revealable();
    }

    protected function mutateFormDataBeforeRegister(array $data): array
    {
        // Validate registration code
        $requiredCode = env('REGISTRATION_CODE');
        
        if (empty($requiredCode)) {
            throw ValidationException::withMessages([
                'registration_code' => 'Registration is currently disabled. Please contact an administrator.',
            ]);
        }

        if (!isset($data['registration_code']) || $data['registration_code'] !== $requiredCode) {
            throw ValidationException::withMessages([
                'registration_code' => 'Invalid registration code. Please check your code and try again.',
            ]);
        }

        // Remove registration_code from data before creating user
        unset($data['registration_code']);
        unset($data['passwordConfirmation']);

        return $data;
    }
}

