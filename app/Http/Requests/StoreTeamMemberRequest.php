<?php

namespace App\Http\Requests;

use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $team = $this->route('team');

        if (! $team instanceof Team) {
            return false;
        }

        $membership = $team->members()->where('user_id', Auth::id())->first();

        if (! $membership) {
            return false;
        }

        if ($membership->role === 'owner') {
            return true;
        }

        return $membership->role === 'admin';
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
                $membership = $team?->members()->where('user_id', Auth::id())->first();

                if ($membership?->role === 'admin' && $value === 'admin') {
                    $fail('An admin can only add member-role users.');
                }
            }],
        ];
    }
}
