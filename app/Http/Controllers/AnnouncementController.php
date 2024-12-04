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
        ->join('admins', 'announcements.admin_id', '=', 'admins.admin_id') // Join with admins table
        ->join('classes', 'announcements.class_id', '=', 'classes.class_id') // Join with classes table
        ->join('sections', 'classes.section_id', '=', 'sections.section_id') // Join with sections table
        ->join('subjects', 'classes.subject_id', '=', 'subjects.subject_id') // Join with subjects table
        ->select(
            'announcements.*', // Select all columns from announcements
            DB::raw("CONCAT(admins.fname, ' ', admins.lname) as admin_name"), // Full admin name
            'subjects.subject_name', // Subject name
            'sections.grade_level' // Grade level
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
}
