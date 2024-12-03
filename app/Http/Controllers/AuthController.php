<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\Subject;
use App\Models\Financial_Statement;
use App\Models\Tuition_Fees;
use App\Models\Payment;
use App\Models\Grade;
use App\Models\Roster;
use App\Models\Enrollment;
use App\Models\Announcement;
use App\Models\Message;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{

    public function upsert(Request $request)
    {
        // Validate incoming request data
        $request->validate([
            '*.LRN' => 'required|string|max:255|unique:students,LRN',
            '*.lname' => 'required|string|max:255',
            '*.fname' => 'required|string|max:255',
            '*.mname' => 'nullable|string|max:255',
            '*.suffix' => 'nullable|string|max:10',
            '*.bdate' => 'nullable|date',
            '*.bplace' => 'nullable|string|max:255',
            '*.gender' => 'nullable|string|max:10',
            '*.religion' => 'nullable|string|max:50',
            '*.address' => 'nullable|string|max:255',
            '*.contact_no' => 'nullable|string|max:15',
            '*.email' => 'required|email|max:255|unique:students,email'
        ]);

        // Prepare data for upsert
        $data = $request->input();

        // Perform upsert operation
        Student::upsert(
            $data,
            ['LRN'], // Unique keys to check for existing records
            ['lname', 'fname', 'mname', 'suffix', 'bdate', 'bplace', 'gender', 'religion', 'address', 'contact_no', 'email'] // Columns to update if found
        );

        return response()->json(['message' => 'Students upserted successfully.'], 200);
    }  

    public function getAttendanceReport($LRN)
    {
        // Fetch the class IDs from the roster for the specific student
        $classIds = DB::table('rosters')->where('LRN', $LRN)->pluck('class_id');

        // If no classes found, return an empty response
        if ($classIds->isEmpty()) {
            return response()->json([
                'attendanceRecords' => [],
                'subjects' => []
            ]);
        }

        // Fetch attendance records for the specific student
        $attendanceRecords = DB::table('attendances')
            ->select('date', 'status', 'LRN', 'class_id')
            ->where('LRN', $LRN)
            ->whereIn('class_id', $classIds)
            ->whereRaw('DAYOFWEEK(date) BETWEEN 2 AND 6') // Monday (2) to Friday (6)
            ->get();

        // Map attendance records to include subject names
        $attendanceRecords = $attendanceRecords->map(function ($record) {
            // Fetch subject name using a subquery or join
            $subject = DB::table('classes')
                ->join('subjects', 'classes.subject_id', '=', 'subjects.subject_id')
                ->where('classes.class_id', $record->class_id)
                ->select('subjects.subject_name')
                ->first();

            return [
                'date' => $record->date,
                'status' => $record->status,
                'LRN' => $record->LRN,
                'subject_name' => optional($subject)->subject_name // Use optional to avoid null errors
            ];
        });

        // Fetch unique subjects based on the class IDs from the roster
        $subjects = DB::table('subjects')
            ->join('classes', 'subjects.subject_id', '=', 'classes.subject_id')
            ->whereIn('classes.class_id', $classIds)
            ->distinct()
            ->select('subjects.subject_name')
            ->get();

        return response()->json([
            'attendanceRecords' => $attendanceRecords,
            'subjects' => $subjects
        ]);
    }

    public function getStudentReport($LRN)
    {
        // Fetch class IDs from the roster for the specific student
        $classIds = Roster::where('LRN', $LRN)->pluck('class_id');

        // If no classes found, return an empty response
        if ($classIds->isEmpty()) {
            return response()->json(['message' => 'No records found for the given LRN.']);
        }

        // Fetch student report for subjects associated with the student's classes
        $studentReport = DB::table('classes')
            ->leftJoin('subjects', 'classes.subject_id', '=', 'subjects.subject_id')
            ->leftJoin('sections', 'classes.section_id', '=', 'sections.section_id')
            ->leftJoin('grades', function ($join) use ($LRN) {
                $join->on('grades.class_id', '=', 'classes.class_id')
                    ->where('grades.LRN', '=', $LRN);
            })
            ->select(
                'subjects.subject_name',
                'grades.term',
                'grades.grade',
                'sections.section_name',
                
            )
            ->whereIn('classes.class_id', $classIds) // Filter by class IDs from roster
            ->orderBy('subjects.subject_name') // Sort by subject name
            ->orderBy('grades.term') // Sort by term
            ->get();

        // Transform data into a more usable format
        $result = [];
        foreach ($studentReport as $record) {
            $result[$record->subject_name][$record->term] = [
                'grade' => $record->grade ?? null, // Use null if grade is not available
                'section' => $record->section_name // Include section name if needed
            ];
        }

        return response()->json($result);
    }

    public function displaySOA(Request $request, $id) {
        $data = DB::table('students')
            ->join('enrollments', 'enrollments.LRN', '=', 'students.LRN')
            ->leftJoin('payments', 'students.LRN', '=', 'payments.LRN')
            ->leftJoin('financial_statements', 'students.LRN', '=', 'financial_statements.LRN')
            ->leftJoin('tuition_fees', 'enrollments.year_level', '=', 'tuition_fees.year_level') // Join with tuition_fees
            ->select(
                'students.LRN',
                'students.lname',
                'students.fname',
                'students.mname',
                'students.suffix',
                'students.gender',
                'students.address',
                'enrollments.year_level',
                'enrollments.contact_no',
                'enrollments.date_register',
                'enrollments.guardian_name',
                'enrollments.public_private',
                'enrollments.school_year',
                'enrollments.regapproval_date',
                'payments.OR_number',
                'payments.amount_paid',
                'payments.proof_payment',
                'payments.created_at',
                'payments.description',
                'tuition_fees.tuition',
                'financial_statements.*', 
                DB::raw('IFNULL(tuition_fees.tuition, 0) - IFNULL(payments.amount_paid, 0) AS remaining_balance') // Calculate remaining balance
            )
            ->where('students.LRN', $id) // Filter by student ID
            ->get(); // Use get() to get all records
            
        if ($data->isNotEmpty()) {
            return response()->json($data, 200);
        } else {
            return response()->json(['message' => 'Student not found'], 404);
        }
    }

    public function getFinancialStatement($LRN)
{
    // Fetch payment history for the given LRN
    $payments = DB::table('payments')
        ->where('LRN', $LRN) // Ensure we're filtering by the correct LRN
        ->get(); // Get payments only from the payments table

    // Fetch enrollment approval status for the given LRN
    $enrollmentStatus = DB::table('enrollments')
        ->where('LRN', $LRN)
        ->select('payment_approval') // Select only the payment approval status
        ->first(); // Get a single record

    // Fetch uploaded documents for the given LRN
    $documents = DB::table('financial_statements')
        ->where('LRN', $LRN)
        ->get();

    return response()->json([
        'payments' => $payments,
        'documents' => $documents,
        'enrollment_status' => $enrollmentStatus, // Include enrollment status if needed
    ]);
}

    // message
    public function getAdmin(){
        $admins = DB::table('admins')
            ->select('admins.admin_id', DB::raw('CONCAT(admins.fname, " ", COALESCE(LEFT(admins.mname, 1), ""), " ", admins.lname) as account_name '))
            ->get()
            ->map(function ($admins) {
                return [
                    'account_id' => $admins->admin_id,
                    'account_name' => $admins->account_name,
                    'type' => 'admin',
                ];
            });

        return response()->json($admins);
    } 

    public function getMessages(Request $request) {
        $uid = $request->input('uid');
    
        // Subquery to get the latest message for each sender
        $latestMessages = DB::table('messages')
            ->select('message_sender', DB::raw('MAX(created_at) as max_created_at'))
            ->where('message_reciever', '=', $uid) // Ensure we only consider messages sent to the user
            ->groupBy('message_sender');
    
        // Main query to get messages
        $msg = DB::table('messages')
            
            ->leftJoin('admins', 'messages.message_sender', '=', 'admins.admin_id') // Join with admins table
            // ->joinSub($latestMessages, 'latest_messages', function ($join) {
            //     $join->on('messages.message_sender', '=', 'latest_messages.message_sender')
            //          ->on('messages.created_at', '=', 'latest_messages.max_created_at');
            // })
            ->where('messages.message_reciever', '=', $uid) // Filter by receiver
            ->select('messages.*', 
                DB::raw('CASE 
                        WHEN messages.message_sender IN (SELECT admin_id FROM admins) THEN CONCAT(admins.fname, " ", LEFT(admins.mname, 1), " ", admins.lname)
                    ELSE NULL
                END as sender_name'))
            ->orderBy('messages.created_at', 'desc')
            ->get();
    
        return response()->json($msg); // Return the messages as JSON response
    }

    public function getConvo(Request $request, $sid) {
        // Initialize the response variable
        $user = null;
    
        // Check if the $sid corresponds to a student
        $student = DB::table('students')
            ->where('students.LRN', $sid)
            ->select('students.LRN', DB::raw('CONCAT(students.fname, " ", LEFT(students.mname, 1), " ", students.lname) as account_name'))
            ->first(); // Use first() to get a single record
    
        if ($student) {
            // If a admin is found, format the response
            $user = [
                'account_id' => $student->LRN,
                'account_name' => $student->account_name,
                'type' => 'student',
            ];
        } else {
            // If no student found, check for a parent
            $admin = DB::table('admins')
            ->where('admins.admin_id', $sid)
            ->select('admins.admin_id', DB::raw('CONCAT(admins.fname, " ", COALESCE(LEFT(admins.mname, 1), ""), ". ", admins.lname) as account_name'))
            ->first(); // Use first() to get a single record
        
        if ($admin) {
            // If an admin is found, format the response
            $user = [
                'account_id' => $admin->admin_id,
                'account_name' => trim($admin->account_name), // Trim to remove any extra spaces
                'type' => 'admin',
            ];
        }
        }
    
        // Initialize the conversation variable
        $convo = [];
    
        // If user is found, fetch the conversation
        if ($user) {
            $uid = $request->input('uid');
    
            $convo = DB::table('messages')
            ->leftJoin('students', 'messages.message_sender', '=', 'students.LRN')
            ->leftJoin('admins', 'messages.message_sender', '=', 'admins.admin_id')
            ->where(function ($query) use ($sid) {
                $query->where('messages.message_sender', $sid)
                    ->orWhere('messages.message_reciever', $sid);
            })
            ->where(function ($query) use ($uid) {
                $query->where('messages.message_sender', $uid)
                    ->orWhere('messages.message_reciever', $uid);
            })
            ->selectRaw("
                messages.*,
                CASE 
                    WHEN messages.message_sender = ? THEN 'me' 
                    ELSE NULL 
                END as me,
                CASE 
                    WHEN messages.message_sender IN (SELECT admin_id FROM admins) THEN CONCAT(admins.fname, ' ', LEFT(admins.mname, 1), '. ', admins.lname)
                    ELSE NULL 
                END as sender_name
            ", [$uid])
            ->get();

        }
    
        // Return the user information and conversation or a not found message
        return response()->json([
            'user' => $user ?: ['message' => 'User  not found'],
            'conversation' => $convo,
        ]);
    }

    public function sendMessage(Request $request) {
        \Log::info('Request Method: ' . $request->method()); // Log the request method

        $validator = Validator::make($request->all(), [
            'message_sender' => 'required|string',
            'message_reciever' => 'required|string',
            'message' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
    
        $message = Message::create([
            'message_sender' => $request->input('message_sender'),
            'message_reciever' => $request->input('message_reciever'),
            'message' => $request->input('message'),
            'message_date' => now()
        ]);
    
        return response()->json(['message' => 'Message sent successfully!', 'data' => $message], 201);
    }

    public function getrecepeints(Request $request)
    {
     $students = DB::table('students')
     ->select(DB::raw('LRN AS receiver_id, CONCAT(fname, " ", lname) AS reciever_name'));
    $admins = DB::table('admins')
        ->select(DB::raw('admin_id AS receiver_id, CONCAT(fname, " ", lname) AS reciever_name'));
    $recipients = $students->unionAll($admins)->get();
    return response()->json($recipients);
    }

    public function composenewmessage(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'message_date' => 'required|date',
            'message_sender' => [
                'required',
                function ($attribute, $value, $fail) {
                    $existsInStudents = DB::table('students')->where('LRN', $value)->exists();
                    $existsInGuardians = DB::table('parent_guardians')->where('guardian_id', $value)->exists();
                    $existsInAdmins = DB::table('admins')->where('admin_id', $value)->exists();
    
                    if (!$existsInStudents && !$existsInGuardians && !$existsInAdmins) {
                        $fail("The selected $attribute is invalid.");
                    }
                },
            ],
            'message_reciever' => [
                'required',
                function ($attribute, $value, $fail) {
                    $existsInStudents = DB::table('students')->where('LRN', $value)->exists();
                    $existsInGuardians = DB::table('parent_guardians')->where('guardian_id', $value)->exists();
                    $existsInAdmins = DB::table('admins')->where('admin_id', $value)->exists();
    
                    if (!$existsInStudents && !$existsInGuardians && !$existsInAdmins) {
                        $fail("The selected $attribute is invalid.");
                    }
                },
            ],
        ]);
    
        try {
            // Create a new message
            $message = new Message();
            $message->message_sender = $validated['message_sender'];
            $message->message_reciever = $validated['message_reciever'];
            $message->message = $validated['message'];
            $message->message_date = $validated['message_date'];
            $message->save();
    
            // Log a success message
            Log::info('Message successfully composed', [
                'message_id' => $message->message_id,
                'sender' => $validated['message_sender'],
                'receiver' => $validated['message_reciever'],
                'message_content' => $validated['message'],
                'message_date' => $validated['message_date'],
            ]);
    
            // Return the updated list of messages
            return $this->getMessages($request);  // Call getMessages method to return updated conversation
        } catch (\Exception $e) {
            // Log any error that occurs
            Log::error('Error sending message: ' . $e->getMessage());
    
            // Return an error response
            return response()->json(['error' => 'Failed to send message'], 500);
        }
    }

    public function notification(Request $request)
{
    // Validate the request to ensure LRN is provided
    $request->validate([
        'LRN' => 'required|string', // Validate LRN
    ]);

    $lrn = $request->input('LRN'); // Get LRN from request

    // Initialize an array to hold all notifications
    $notifications = [];

    // Fetch latest announcements (not filtered by student)
    $announcements = Announcement::orderBy('created_at', 'desc')
        ->take(5) // Limit to the latest 5 announcements
        ->get(['ancmnt_id', 'announcement as message', 'created_at']); // Adjust fields as necessary

    foreach ($announcements as $announcement) {
        $notifications[] = [
            'id' => $announcement->ancmnt_id,
            'message' => $announcement->message,
            'created_at' => $announcement->created_at,
            'type' => 'announcement', // Add type for identification
        ];
    }

    // Fetch latest messages where the message_reciever matches the student's LRN
    $messages = Message::where('message_reciever', $lrn) // Filter messages by receiver's LRN
        ->orderBy('created_at', 'desc')
        ->take(5) // Limit to the latest 5 messages
        ->get(['message_id', 'message as message', 'created_at']); // Adjust fields as necessary

    foreach ($messages as $message) {
        $notifications[] = [
            'id' => $message->message_id,
            'message' => $message->message,
            'created_at' => $message->created_at,
            'type' => 'message', // Add type for identification
        ];
    }

    // Fetch latest payment approvals from enrollment table for this student
    $approvals = Enrollment::where('LRN', $lrn) // Filter for records with the student's LRN
        ->whereNotNull('payment_approval') // Filter for records with payment approval
        ->orderBy('created_at', 'desc')
        ->take(5) // Limit to the latest 5 approvals
        ->get(['enrol_id', 'payment_approval as message', 'created_at']); // Adjust fields as necessary

    foreach ($approvals as $approval) {
        $notifications[] = [
            'id' => $approval->enrol_id,
            'message' => $approval->message,
            'created_at' => $approval->created_at,
            'type' => 'payment_approval', // Add type for identification
        ];
    }

    // Sort all notifications by created_at date in descending order and limit to 10 total notifications
    usort($notifications, function ($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    return response()->json(array_slice($notifications, 0, 10)); // Limit total notifications to 10
}

}
