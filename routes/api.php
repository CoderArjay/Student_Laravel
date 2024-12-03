<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TuitionFeesController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\KlassController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\DsfController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\FinancialStatementController;


Route::get('/user', function (Request $request) {
    return $request->user();
});

Route::apiResource('section', SectionController::class);
Route::apiResource('students', StudentController::class);
Route::apiResource('subjects', SubjectController::class);
Route::apiResource('admins', AdminController::class);
Route::apiResource('class', KlassController::class);
Route::apiResource('announcement', AnnouncementController::class);
Route::apiResource('attendance', AttendanceController::class);
Route::apiResource('grades', GradeController::class);
Route::apiResource('tuition_fees', TuitionFeesController::class);
Route::apiResource('financial_statement', FinancialStatementController::class);

Route::get('/notifications', [AuthController::class, 'notification']);

Route::post('/newAdmin', [AuthController::class, 'newAdmin']);
Route::get('/classes/{LRN}', [KlassController::class, 'classDisplay']);
Route::post('/student/assign-section/{LRN}', [KlassController::class, 'assignSection']);
Route::post('/update-password', [StudentController::class, 'updatePassword'])->name('update.password');

//Getting the Tuition and fees of the student 
// Route::post('/messages/send', [MessageController::class, 'sendMessage']);
Route::get('/messages', [MessageController::class, 'getMessages']);
Route::post('/students/upload-profile', [StudentController::class, 'uploadProfile']);
Route::get('/attendance-report/{LRN}', [AuthController::class, 'getAttendanceReport']);
Route::get('/displaySOA/{id}', [AuthController::class, 'displaySOA']);
Route::get('/student-report/{LRN}', [AuthController::class, 'getStudentReport']);
Route::put('/students/{student}', [StudentController::class, 'update']);
Route::get('/students/{LRN}/profile-image', [StudentController::class, 'getProfileImage']);
Route::get('/students/check/{lrn}', [StudentController::class, 'checkStudentInfo']);

//Latest-Message
Route::get('/latest-messages', [MessageController::class, 'latestMessages']);

// Route::middleware('auth:sanctum')->post('/logout', [UserController::class, 'logout']);
Route::get('/financial-statement/{LRN}', [AuthController::class, 'getFinancialStatement']);
Route::post('/Studentlogin', [StudentController::class, 'login']);
Route::middleware('auth:sanctum')->post('/Studentlogout', [StudentController::class, 'Studentlogout']);

 // Message 
 Route::get('/getAdmin', [AuthController::class, 'getAdmin']); //done
 Route::get('/getMessages', [AuthController::class, 'getMessages']); //
 Route::get('/getConvo/{sid}', [AuthController::class, 'getConvo']); //
 Route::post('/sendMessage', [AuthController::class, 'sendMessage']);
 Route::get('/getrecepeints', [AuthController::class, 'getrecepeints']); //
 Route::post('/composemessage', [AuthController::class, 'composenewmessage']); //


//Enrollment Process
Route::apiResource('enrollments', EnrollmentController::class);
Route::post('/enrollment', [StudentController::class, 'enrollment']);
Route::get('/students/{LRN}', [StudentController::class, 'show']);
Route::get('/enrollments/{LRN}', [EnrollmentController::class, 'getEnrollmentById']);
Route::post('/enrollments', [EnrollmentController::class, 'create']);
Route::get('/students/{LRN}/tuition-details', [TuitionFeesController::class, 'getTuitionDetails']);
Route::post('/payments', [PaymentController::class, 'store']);
Route::get('/payment/{lrn}', [PaymentController::class, 'getPaymentDetailsWithProof']);

Route::get('/student/{lrn}', [StudentController::class, 'getStudentData']);
// Route::post('/enrollment/process/{lrn}', [EnrollmentController::class, 'enrollmentProcess']);

//DSF ROUTES
Route::post('/payment', [PaymentController::class, 'stupaymentdent']);
Route::post('/register', [DsfController::class, 'register']);
Route::post('/login', [DsfController::class, 'login']);
Route::post('/logout', [DsfController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/display', [DsfController::class, 'display']);
Route::get('/receiptdisplay/{id}', [DsfController::class, 'receiptdisplay']);
Route::get('/approveEnrollment/{id}', [DsfController::class, 'approveEnrollment']);
Route::get('/displaygrade', [DsfController::class, 'displaygrade']);
Route::get('/displaySOA/{id}', [DsfController::class, 'displaySOA']);
Route::get('/displayStudent', [DsfController::class, 'displayStudent']);
Route::put('/updatepayment/{id}', [DsfController::class, 'updatepayment']);
