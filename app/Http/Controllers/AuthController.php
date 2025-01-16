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
        // Fetch the student's information to get their current semester
        $student = DB::table('students')->where('LRN', $LRN)->first();
        if (!$student) {
            return response()->json([
                'attendanceRecords' => [],
                'subjects' => []
            ]);
        }
    
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
    
        // Fetch unique subjects based on the class IDs from the roster, including semester information
        $subjects = DB::table('subjects')
            ->join('classes', 'subjects.subject_id', '=', 'classes.subject_id')
            ->whereIn('classes.class_id', $classIds)
            ->distinct()
            ->select('subjects.subject_name', 'classes.semester') // Include semester in the selection
            ->get();
    
        // Filter subjects by the current semester
        $currentSemester = optional($student)->semester; // Get current semester from student data
    
        if ($currentSemester) {
            $subjects = $subjects->filter(function ($subject) use ($currentSemester) {
                return isset($subject->semester) && $subject->semester === $currentSemester;
            });
        }
    
        return response()->json([
            'attendanceRecords' => $attendanceRecords,
            'subjects' => $subjects
        ]);
    }
    

    public function getStudentReport($LRN)
{
    // Step 1: Get the latest school year
        $latestSchoolYear = DB::table('enrollments')
            ->where('LRN', $LRN) // Ensure you are looking for the specific student
            ->max('school_year');
    
        // Step 2: Query to get the grades for the latest school year
        $grades = DB::table('rosters')
            ->join('students', 'rosters.LRN', '=', 'students.LRN')
            ->join('enrollments', 'students.LRN', '=', 'enrollments.LRN')
            ->join('classes', 'rosters.class_id', '=', 'classes.class_id')
            ->join('sections', 'classes.section_id', '=', 'sections.section_id')
            ->leftJoin('subjects', 'classes.subject_id', '=', 'subjects.subject_id')
            ->leftJoin('grades', function($join) {
                $join->on('rosters.LRN', '=', 'grades.LRN')
                     ->on('rosters.class_id', '=', 'grades.class_id');
            })
            ->select(
                'students.LRN',
                DB::raw('CONCAT(students.fname, " ", LEFT(students.mname, 1), ". ", students.lname) AS student_name'),
                'students.contact_no AS student_contact_no',
                'subjects.subject_id',
                'subjects.subject_name',
                'subjects.strand',
                'subjects.grade_level',
                'enrollments.school_year',
                'classes.semester',
                DB::raw('MAX(CASE WHEN grades.term = "First Quarter" THEN grades.grade ELSE NULL END) AS First_Quarter'),
                DB::raw('MAX(CASE WHEN grades.term = "Second Quarter" THEN grades.grade ELSE NULL END) AS Second_Quarter'),
                DB::raw('MAX(CASE WHEN grades.term = "Third Quarter" THEN grades.grade ELSE NULL END) AS Third_Quarter'),
                DB::raw('MAX(CASE WHEN grades.term = "Fourth Quarter" THEN grades.grade ELSE NULL END) AS Fourth_Quarter'),
                DB::raw('MAX(CASE WHEN grades.term = "Midterm" THEN grades.grade ELSE NULL END) AS Midterm'),
                DB::raw('MAX(CASE WHEN grades.term = "Final" THEN grades.grade ELSE NULL END) AS Final')
            )
            ->where('students.LRN', '=', $LRN) // Filter by the student's LRN
            ->where('enrollments.school_year', '=', $latestSchoolYear) // Filter by the latest school year
            ->groupBy(
                'students.LRN',
                'students.fname',
                'students.mname',
                'students.lname',
                'students.contact_no',
                'subjects.subject_id',
                'subjects.subject_name',
                'subjects.strand',
                'subjects.grade_level',
                'enrollments.school_year',
                'classes.semester',
            )
            ->orderBy('subjects.subject_name')
            ->get();
    
        return response()->json($grades); // Return results as JSON
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
            ->where('LRN', $LRN)
            ->get();

        // Fetch enrollment approval status for the given LRN
        $enrollmentStatus = DB::table('enrollments')
            ->where('LRN', $LRN)
            ->select('payment_approval')
            ->first();

        // Fetch uploaded documents for the given LRN
        $documents = DB::table('financial_statements')
            ->where('LRN', $LRN)
            ->get();

        // Map documents to include full URL for images
        $documentsWithUrls = $documents->map(function ($document) {
            // Assuming 'filename' is the column that holds the image filename
            $document->url = asset('/storage/financials/' . $document->filename);
            return $document;
        });

        return response()->json([
            'payments' => $payments,
            'documents' => $documentsWithUrls,
            'enrollment_status' => $enrollmentStatus,
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

    public function markAsRead(Request $request) {
        $sid = $request->input('sid'); // The ID of the user whose messages are being marked as read

        // Update the read_at timestamp for all messages involving the user
        $read = DB::table('messages')
            ->where(function($query) use ($sid) {
                $query->where('messages.message_sender', '=', $sid) // Messages sent by the user
                      ->orWhere('messages.message_reciever', '=', $sid); // Messages received by the user
            })
            ->update(['read_at' => now()]); // Set the read_at timestamp to the current time
    
        return response()->json(['success' => true, 'updated_count' => $read]);
    }

    public function getUnreadCount(Request $request)
    {
        $uid = $request->input('uid'); // Get the user ID from the request

        // Count unread messages for the user
        $unreadCount = DB::table('messages')
            ->where('message_reciever', $uid)
            ->where('read_at', null)
            ->count();

        // return response()->json(['unread_count' => $unreadCount]);
        return $unreadCount;
    }

 public function getMessages(Request $request) {
        $uid = $request->input('uid');
    
        // Main query to get messages
        $msg = DB::table('messages')
            ->leftJoin('admins', 'messages.message_sender', '=', 'admins.admin_id') // Sender admin details
            ->leftJoin('admins as receiver_admins', 'messages.message_reciever', '=', 'receiver_admins.admin_id') // Receiver admin details
            ->where(function($query) use ($uid) {
                $query->where('messages.message_sender', '=', $uid) // Messages sent by the user
                      ->orWhere('messages.message_reciever', '=', $uid); // Messages received by the user
            })
            ->select(
                'messages.*', 
                DB::raw('
                    CASE 
                        WHEN messages.message_sender = admins.admin_id THEN 
                            CONCAT(
                                admins.fname, " ", 
                                CASE 
                                    WHEN admins.mname IS NOT NULL THEN CONCAT(LEFT(admins.mname, 1), " ") 
                                    ELSE "" 
                                END, 
                                admins.lname
                            )
                        WHEN messages.message_reciever = receiver_admins.admin_id THEN 
                            CONCAT(
                                receiver_admins.fname, " ", 
                                CASE 
                                    WHEN receiver_admins.mname IS NOT NULL THEN CONCAT(LEFT(receiver_admins.mname, 1), " ") 
                                    ELSE "" 
                                END, 
                                receiver_admins.lname
                            )
                        ELSE NULL
                    END as sender_name
                '),
                DB::raw('IF(messages.read_at IS NULL, 0, 1) as is_read')
            )
            ->havingRaw('sender_name is not null')
            ->orderBy('messages.created_at', 'desc')
            ->get();
    
        return $msg;
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
        // Fetch admins and concatenate their first and last names, including their role
        $admins = DB::table('admins')
            ->select(
                'admin_id AS receiver_id', 
                DB::raw('CONCAT(fname, " ", lname) AS receiver_name'),
                'role' // Add the role field to the selection
            )
            ->where('role', '<>', 'dsf'); // Exclude admins with the role 'dsf'
    
        // Execute the query and get the results
        $recipients = $admins->get();
    
        // Return the results as a JSON response
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

    


}
