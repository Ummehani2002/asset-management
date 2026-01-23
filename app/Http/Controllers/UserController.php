<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'role'     => 'required|in:admin,user',
        ]);

        User::create([
            'name'     => $request->name,
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role ?? 'user'
        ]);

        return redirect()->route('users.index')->with('success', 'User created successfully!');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'username' => 'required|unique:users,username,'.$user->id,
            'role'     => 'required|in:admin,user',
        ]);

        $user->username = $request->username;
        $user->role = $request->role;
        
        // Update password if provided
        if ($request->filled('password')) {
            $request->validate([
                'password' => 'required|min:6|confirmed',
            ]);
            $user->password = Hash::make($request->password);
        }
        
        $user->save();

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    public function destroy($id)
    {
        User::destroy($id);
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
            
            // Headers
            fputcsv($file, ['#', 'Name', 'Username', 'Email', 'Created At']);

            // Data
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
