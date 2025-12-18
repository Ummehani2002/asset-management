<?php
namespace App\Http\Controllers;
use App\Models\SimcardTransaction;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SimcardTransactionController extends Controller
{
    // Show unified SIM transaction form
    public function create()
    {
        $projects = Project::select('id', 'project_id', 'project_name', 'entity')
                           ->orderBy('project_id')
                           ->get();

        // Get available SIMs for assignment and assigned SIMs for return
        $availableSimcards = SimcardTransaction::getAvailableSimcards();
        $assignedSimcards = SimcardTransaction::getAssignedSimcards();

        return view('simcards.form', compact('projects', 'availableSimcards', 'assignedSimcards'));
    }

    // Store assignment or return
    public function store(Request $request)
    {
        // Custom validation rules based on transaction type
        $rules = [
            'transaction_type' => 'required|in:assign,return',
            'simcard_number'   => 'required|max:100',
            'mrc'              => 'nullable|numeric',
            'pm_dc'            => 'nullable|max:100',
        ];

        if ($request->transaction_type === 'assign') {
            $rules['project_id'] = 'required|exists:projects,id';
            $rules['issue_date'] = 'required|date';
        } else {
            $rules['return_date'] = 'required|date';
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();
        try {
            if ($request->transaction_type === 'assign') {
                $project = Project::findOrFail($request->project_id);

                SimcardTransaction::create([
                    'transaction_type' => 'assign',
                    'simcard_number'   => $request->simcard_number,
                    'project_id'       => $project->id,
                    'project_name'     => $project->project_name,
                    'entity'           => $project->entity,
                    'mrc'              => $request->mrc,
                    'issue_date'       => $request->issue_date,
                    'pm_dc'            => $request->pm_dc,
                ]);

                $message = 'SIM card assigned successfully!';
            } else {
                // For return - find the latest assignment without return_date
                $assignment = SimcardTransaction::where('simcard_number', $request->simcard_number)
                    ->where('transaction_type', 'assign')
                    ->whereNull('return_date')
                    ->latest('issue_date')
                    ->first();

                if (!$assignment) {
                    throw new \Exception('No active assignment found for this SIM card. It may already be returned.');
                }

                // Update the assignment record with return date
                $assignment->update(['return_date' => $request->return_date]);

                // Create return transaction record
                SimcardTransaction::create([
                    'transaction_type' => 'return',
                    'simcard_number'   => $request->simcard_number,
                    'project_id'       => $assignment->project_id,
                    'project_name'     => $assignment->project_name,
                    'entity'           => $assignment->entity,
                    'mrc'              => $assignment->mrc,
                    'issue_date'       => $assignment->issue_date,
                    'return_date'      => $request->return_date,
                    'pm_dc'            => $assignment->pm_dc,
                ]);

                $message = 'SIM card returned successfully!';
            }

            DB::commit();
            return redirect()->route('simcards.index')->with('success', $message);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('SIM Card Transaction Error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Operation failed: ' . $e->getMessage())->withInput();
        }
    }

    // Get SIM details for return form
    public function getSimDetails($simcardNumber)
    {
        $assignment = SimcardTransaction::where('simcard_number', $simcardNumber)
            ->where('transaction_type', 'assign')
            ->whereNull('return_date')
            ->first();

        if (!$assignment) {
            return response()->json(['error' => 'SIM not found or already returned'], 404);
        }

        return response()->json([
            'project_id'   => $assignment->project_id,
            'project_name' => $assignment->project_name,
            'entity'       => $assignment->entity,
            'mrc'          => $assignment->mrc,
            'issue_date'   => $assignment->issue_date, // keep as string
            'pm_dc'        => $assignment->pm_dc,
        ]);
    }

    // Index page - show all transactions
    public function index()
    {
        $transactions = SimcardTransaction::orderBy('created_at', 'desc')->paginate(20);

        return view('simcards.index', compact('transactions'));
    }
}
