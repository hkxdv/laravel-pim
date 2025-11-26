<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Requests;

use App\Models\StaffUsers;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

/**
 * Request para validación de datos de formulario de usuarios del staff.
 * Maneja tanto la creación como la actualización de usuarios.
 */
final class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var StaffUsers|null $user */
        $user = Auth::user();

        return $user && $user->can('access-admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'min:3', 'max:128'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:42'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,name'], // Validar que cada rol exista
            'avatar' => ['nullable', 'string', 'max:2048'], // URL o base64 de la imagen
            'auto_verify_email' => ['nullable', 'boolean'], // Opción para verificar automáticamente el email
        ];

        // Si es una actualización, obtener el ID del usuario para excluirlo de la validación unique
        $userParam = $this->route('user');
        $userId = null;

        if ($userParam) {
            // Si es un objeto, obtener el ID directamente
            if (is_object($userParam) && property_exists($userParam, 'id')) {
                $userId = $userParam->id;
            }
            // Si es un string o número, usarlo como ID
            elseif (is_string($userParam) || is_numeric($userParam)) {
                $userId = $userParam;
            }
        }

        // Regla única para el email, excepto para el usuario actual en actualizaciones
        if ($userId) {
            $userIdStr = is_scalar($userId) ? (string) $userId : '';
            $rules['email'][] = sprintf('unique:staff_users,email,%s', $userIdStr);
        } else {
            $rules['email'][] = 'unique:staff_users,email';
            $rules['password'] = [
                'required',
                'string',
                'min:8',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ];
        }

        // En actualizaciones, la contraseña es opcional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['password'] = [
                'nullable',
                'string',
                'min:8',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio',
            'name.min' => 'El nombre debe tener al menos :min caracteres',
            'name.max' => 'El nombre no debe exceder los :max caracteres',
            'email.required' => 'El correo electrónico es obligatorio',
            'email.email' => 'Debe ingresar un correo electrónico válido',
            'email.unique' => 'Este correo electrónico ya está en uso',
            'email.max' => 'El correo electrónico no debe exceder los :max caracteres',
            'password.required' => 'La contraseña es obligatoria',
            'password.min' => 'La contraseña debe tener al menos :min caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'password.mixed' => 'La contraseña debe contener al menos una letra mayúscula y una minúscula',
            'password.numbers' => 'La contraseña debe contener al menos un número',
            'password.symbols' => 'La contraseña debe contener al menos un símbolo',
            'password.uncompromised' => 'La contraseña proporcionada ha aparecido en una filtración de datos. Por favor, elija una contraseña diferente.',
            'roles.required' => 'Debe seleccionar al menos un rol',
            'roles.min' => 'Debe seleccionar al menos un rol',
            'roles.*.exists' => 'Uno de los roles seleccionados no es válido',
            'avatar.max' => 'La URL o datos de la imagen no deben exceder :max caracteres',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        // Validación personalizada para roles protegidos
        $validator->after(function (Validator $v): void {
            if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
                // Solo aplicar en actualizaciones
                $user = $this->route('user');
                if ($user instanceof StaffUsers) {
                    // Consideramos protegido a cualquier usuario que tenga roles ADMIN o DEV
                    $rolesInput = (array) $this->input('roles', []);
                    $requestRoles = array_map(
                        mb_strtoupper(...),
                        array_values(array_filter($rolesInput, is_string(...)))
                    );

                    $currentProtectedRoles = $user->roles
                        ->pluck('name')
                        ->map(
                            fn ($name): string => mb_strtoupper(
                                is_string($name) ? $name : ''
                            )
                        )
                        ->filter(
                            fn ($name): bool => in_array($name, ['ADMIN', 'DEV'], true)
                        )
                        ->values()
                        ->all();

                    // Verificar que todos los roles protegidos actuales sigan presentes en la solicitud
                    $remainingProtectedRoles = array_intersect(
                        $currentProtectedRoles,
                        $requestRoles
                    );

                    if (count($remainingProtectedRoles) !== count($currentProtectedRoles)) {
                        $v->errors()->add(
                            'roles',
                            'No se pueden remover roles protegidos de un usuario administrador'
                        );
                    }
                }
            }
        });
    }
}
