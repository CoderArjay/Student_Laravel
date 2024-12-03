<?php

namespace App\Http\Controllers;

use App\Models\FinancialStatement;
use Illuminate\Http\Request;
use App\Http\Requests\StoreFinancialStatementRequest;
use App\Http\Requests\UpdateFinancialStatementRequest;

class FinancialStatementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $financial_statments = FinancialStatment::all();
        return $financial_statments;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validateData = $request->validate([
            'LRN' => 'required|exists:students,LRN',
            'filename' => 'required|string|max:255',
            'date_uploaded' => 'required|date',
        ]);

        $financial_statement = new FinancialStatement();
        $financial_statement->LRN = $validateData['LRN']; 
        $financial_statement->filename = $validateData['filename'];
        $financial_statement->date_uploaded = now(); 

        $financial_statement->save();

        return response()->json($financial_statement, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(FinancialStatement $financialStatement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFinancialStatementRequest $request, FinancialStatement $financialStatement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FinancialStatement $financialStatement)
    {
        //
    }
}
