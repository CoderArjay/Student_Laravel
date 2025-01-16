<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Requests\UpdateMessageRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function latestMessages()
    {
        $latestMessages = DB::table('messages')
            ->select('messages.*', 'admins.fname', 'admins.lname', 'admins.admin_pic')
            ->join('admins', 'messages.message_sender', '=', 'admins.admin_id') // Adjust as necessary
            ->whereIn('messages.message_id', function($query) {
                $query->select(DB::raw('MAX(message_id)'))
                    ->from('messages')
                    ->groupBy('message_sender'); // Assuming message_sender is used to identify the admin
            })
            ->orderBy('messages.created_at', 'desc')
            ->get();

        return response()->json($latestMessages);
    }

    // public function latestMessages(Request $request)
    // {
    //     $uid = $request->query('uid'); // Get UID from query parameters

    //     // Fetch messages for the specific student using the query builder
    //     $messages = DB::table('messages')
    //         ->where('LRN', $uid) // Assuming 'student_id' is the column in your messages table
    //         ->orderBy('created_at', 'desc') // Order by creation date descending
    //         ->take(10) // Limit to the latest 10 messages
    //         ->get();

    //     return response()->json($messages);
    // }

    public function sendMessage(Request $request) {
        $validator = Validator::make($request->all(), [
            'message_sender' => 'required|string',
            'message_reciever' => 'required|string', // Ensure this is required
            'message' => 'required|string|max:5000',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
    
        $message = Message::create([
            'message_sender' => $request->input('message_sender'),
            'message_reciever' => $request->input('message_reciever'),
            'message' => $request->input('message'),
            'message_date' => now(), // Assuming you want to set the date here as well
        ]);
    
        return response()->json(['message' => 'Message sent successfully!', 'data' => $message], 201);
    }

    // Get messages for a specific user (either admin or student)
    public function getMessages(Request $request) {
        $uid = $request->input('uid');
    
        // Subquery to get the latest message for each admin sender
        $latestMessages = DB::table('messages')
            ->select('message_sender', DB::raw('MAX(created_at) as max_created_at'))
            ->where('message_reciever', '=', $uid) // Ensure we only consider messages sent to the user
            ->whereIn('message_sender', function($query) {
                $query->select('admin_id')->from('admins'); // Only consider messages from admins
            })
            ->groupBy('message_sender');
    
        // Main query to get messages from admins
        $messages = DB::table('messages')
            ->leftJoin('admins', 'messages.message_sender', '=', 'admins.admin_id') // Join with admins table
            ->joinSub($latestMessages, 'latest_messages', function ($join) {
                $join->on('messages.message_sender', '=', 'latest_messages.message_sender')
                     ->on('messages.created_at', '=', 'latest_messages.max_created_at');
            })
            ->where('messages.message_reciever', '=', $uid) // Filter by receiver
            ->whereIn('messages.message_sender', function($query) {
                $query->select('admin_id')->from('admins'); // Only include messages from admins
            })
            ->select('messages.*', 
                DB::raw('CONCAT(admins.fname, " ", LEFT(admins.mname, 1), ". ", admins.lname) as sender_name')) // Get sender's name
            ->orderBy('messages.created_at', 'desc')
            ->get();
    
        return response()->json($messages); // Return the messages as JSON response
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMessageRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Message $message)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMessageRequest $request, Message $message)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Message $message)
    {
        //
    }
}
