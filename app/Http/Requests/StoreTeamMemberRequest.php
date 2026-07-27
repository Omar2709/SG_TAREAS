<?php

namespace App\Http\Requests;

use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $team = $this->route('team');

        if (! $team instanceof Team) {
            return false;
        }

        if (! $team->hasMember($this->user())) {
            return false;
        }

        if ($team->userIsOwner($this->user())) {
            return true;
        }

        return $team->roleOf($this->user()) === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['required', 'in:member,admin', function (string $attribute, mixed $value, \Closure $fail): void {
                $team = $this->route('team');
                $membershipRole = $team instanceof Team ? $team->roleOf($this->user()) : null;

                if ($membershipRole === 'admin' && $value === 'admin') {
                    $fail('An admin can only add member-role users.');
                }
            }],
        ];
    }
}
