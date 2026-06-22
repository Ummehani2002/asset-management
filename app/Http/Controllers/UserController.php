<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Rules\AllowedEmailDomain;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required',
            'username' => 'required|unique:users',
            'email'    => ['required', 'email', 'unique:users', new AllowedEmailDomain],
            'password' => 'required|min:8|confirmed',
            'role'     => 'required|in:admin,user',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(),
        ]);
        $user->role = $request->role;
        $user->save();

        return redirect()->route('users.index')->with('success', 'User created successfully!');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $employees = Employee::orderBy('name')->orderBy('entity_name')->get(['id', 'name', 'entity_name', 'employee_id']);
        return view('users.edit', compact('user', 'employees'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name'        => 'required|string|max:255',
            'username'    => 'required|unique:users,username,'.$user->id,
            'email'       => ['required', 'email', new AllowedEmailDomain],
            'role'        => 'required|in:admin,user',
            'employee_id' => 'nullable|exists:employees,id',
        ]);

        if ($user->isAdmin() && $request->role !== 'admin' && User::where('role', 'admin')->count() <= 1) {
            throw new AccessDeniedHttpException('Cannot remove the last administrator account.');
        }

        $user->name = $request->name;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->role = $request->role;
        $user->employee_id = $request->employee_id ?: null;

        if ($request->filled('password')) {
            $request->validate([
                'password' => 'required|min:8|confirmed',
            ]);
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            throw new AccessDeniedHttpException('Cannot delete the last administrator account.');
        }

        if ($user->id === auth()->id()) {
            throw new AccessDeniedHttpException('You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    public function export(Request $request)
    {
        $users = User::all();
        $format = $request->get('format', 'pdf');

        if ($format === 'csv') {
            return $this->exportCsv($users);
        } else {
            return $this->exportPdf($users);
        }
    }

    private function exportPdf($users)
    {
        $pdf = \PDF::loadView('users.export-pdf', compact('users'));
        return $pdf->download('users-report-' . date('Y-m-d') . '.pdf');
    }

    private function exportCsv($users)
    {
        $filename = 'users-report-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['#', 'Name', 'Username', 'Email', 'Created At']);

            foreach ($users as $index => $user) {
                fputcsv($file, [
                    $index + 1,
                    $user->name,
                    $user->username,
                    $user->email,
                    $user->created_at ? $user->created_at->format('Y-m-d') : 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
