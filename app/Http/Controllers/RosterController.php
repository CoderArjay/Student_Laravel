<?php

namespace App\Http\Controllers;

use App\Models\Roster;
use Illuminate\Http\Request;
use App\Http\Requests\StoreRosterRequest;
use App\Http\Requests\UpdateRosterRequest;

class RosterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Roster $roster)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRosterRequest $request, Roster $roster)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Roster $roster)
    {
        //
    }
}
