<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Requests\UpdateAnnouncementRequest;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
{
    // Fetch announcements with admin, subject, and grade level using query builder
    $announcements = DB::table('announcements')
        ->leftJoin('admins', 'announcements.admin_id', '=', 'admins.admin_id') // Left join with admins table
        ->leftJoin('classes', 'announcements.class_id', '=', 'classes.class_id') // Left join with classes table
        ->leftJoin('sections', 'classes.section_id', '=', 'sections.section_id') // Left join with sections table
        ->leftJoin('subjects', 'classes.subject_id', '=', 'subjects.subject_id') // Left join with subjects table
        ->select(
            'announcements.*', // Select all columns from announcements
            DB::raw("CONCAT(admins.fname, ' ', admins.lname) as admin_name"), // Full admin name
            'subjects.subject_name', // Subject name (may be null)
            'sections.grade_level' // Grade level (may be null)
        )
        ->orderBy('created_at', 'desc') // Order by created_at descending
        ->get();

    return response()->json($announcements);
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validateData = $request->validate([
            'title' => 'required|string|max:255',
            'announcement' => 'required|string|max:5000',
            'admin_id' => 'required|exists:admins,admin_id',
            'class_id' => 'required|exists:klasses,class_id'
        ]);

        $announcements = Announcement::create($validateData);
        return response()->json($announcements, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Announcement $announcement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAnnouncementRequest $request, Announcement $announcement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Announcement $announcement)
    {
        //
    }

    public function notification(Request $request)
    {
        // Validate the request to ensure LRN is provided
        $request->validate([
            'LRN' => 'required|string', // Validate LRN
        ]);
    
        $lrn = $request->input('LRN'); // Get LRN from request
    
        // Fetch latest announcements that have not been viewed (view is null)
        $announcements = Announcement::whereNull('view') // Only get announcements that have not been viewed
            ->orderBy('created_at', 'desc')
            ->take(5) // Limit to the latest 5 announcements
            ->get(['ancmnt_id', 'title', 'created_at']); // Adjust fields as necessary
    
        return response()->json($announcements); // Return all notifications without limiting
    }
    
    

    public function viewed(Request $request)
    {
        // Validate the request to ensure sid is provided
        $request->validate([
            'sid' => 'required|string', // Validate sid
        ]);
    
        $sid = $request->input('sid'); // Get SID from request
    
        // Update the view timestamp for all announcements involving the user
        $updatedCount = DB::table('announcements')
            ->where('admin_id', '=', $sid) // Assuming admin_id is linked to announcements for this example
            ->update(['view' => now()]); // Set the view timestamp to the current time
    
        return response()->json(['success' => true, 'updated_count' => $updatedCount]);
    }
}
