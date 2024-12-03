<?php

namespace App\Http\Controllers;

use App\Models\Klass;
use App\Models\Subject;
use App\Models\Admin;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use App\Http\Requests\StoreKlassRequest;
use App\Http\Requests\UpdateKlassRequest;  
use Illuminate\Support\Facades\DB;


class KlassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $klass = Klass::with(['subject', 'admin'])->get(); 
        return response()->json($klass);
    }

    public function classDisplay($LRN)
    {
        // Fetch the student's enrollment information based on LRN
        $enrollment = Enrollment::where('LRN', $LRN)->first();

        if (!$enrollment) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        // Query to get classes based on the student's LRN from the roster
        $klasses = DB::table('rosters')
            ->join('classes', 'rosters.class_id', '=', 'classes.class_id')
            ->join('subjects', 'classes.subject_id', '=', 'subjects.subject_id')
            ->join('admins', 'classes.admin_id', '=', 'admins.admin_id')
            ->select(
                'classes.room',
                'subjects.subject_name',
                'admins.lname',
                'admins.fname',
                'classes.time',
                'classes.schedule'
            )
            ->where('rosters.LRN', $LRN) // Filter by the student's LRN
            ->get();

        return response()->json([
            'enrollment' => $enrollment,
            'classes' => $klasses
        ]);
    }

    public function assignSection(Request $request, $LRN) {
        // Validate incoming request
        $request->validate([
            'section_id' => 'required|exists:sections,section_id', // Ensure section_id exists in sections table
        ]);
    
        // Fetch the student's enrollment record
        $enrollment = Enrollment::where('LRN', $LRN)->first();
    
        if (!$enrollment) {
            return response()->json(['message' => 'Student not found'], 404);
        }
    
        // Get the student's grade level
        $gradeLevel = $enrollment->grade_level;
    
        // Fetch the section details based on section_id
        $section = DB::table('sections')->where('section_id', $request->section_id)->first();
    
        if (!$section) {
            return response()->json(['message' => 'Section not found'], 404);
        }
    
        // Check if the section's grade level matches the student's grade level
        if ($section->grade_level !== $gradeLevel) {
            return response()->json(['message' => 'The selected section does not match the student\'s grade level'], 400);
        }
    
        // Update the section_id for the enrollment
        $enrollment->section_id = $request->section_id;
    
        if ($enrollment->save()) {
            return response()->json(['message' => 'Student assigned to section successfully']);
        } else {
            return response()->json(['message' => 'Failed to assign student to section'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validateData = $request->validate([
            'admin_id' => 'required|exists:admins,admin_id',
            'subject_id' => 'required|exists:subjects,subject_id',
            'section_id' => 'required|exists:sections,section_id',
            'room' => 'required|string|max:255',
            'schedule' => 'required|string|max:255',
            'time' => 'required|string|max:255',
        ]);

        $klass = Klass::create($validateData);
        return response()->json($klass, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Klass $klass)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateKlassRequest $request, Klass $klass)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Klass $klass)
    {
        //
    }
}
