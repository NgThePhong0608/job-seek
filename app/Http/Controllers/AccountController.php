<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobType;
use App\Models\SavedJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;


class AccountController extends Controller
{
    public function profile()
    {
        return view('front.account.profile');
    }

    public function updateProfile(Request $request)
    {
        $id = Auth::user()->id;

        $validator = Validator::make($request->all(), [
            'name' => 'required|min:5|max:25',
            // check the email unique rule with the user id to ignore the current user email
            // if the email is not changed then it will ignore the current user email
            // otherwise, it will check the email unique rule with the other users
            'email' => 'required|email|unique:users,email,' . $id . ',id',
        ]);

        if ($validator->passes()) {
            // find the user by id
            $user = User::find($id);

            // update the user data
            $user->name = $request->name;
            $user->email = $request->email;
            $user->designation = $request->designation;
            $user->mobile = $request->mobile;

            $user->save();

            session()->flash('success', 'User profile updated successfully!');

            return response()->json([
                'status' => true,
                'errors' => [],
            ]);
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ]);
        }
    }

    public function updateProfilePicture(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = Auth::user();

        DB::beginTransaction();
        try {
            // 1. Xoá ảnh cũ
            if ($user->image) {
                Storage::disk('public')->delete([
                    "profile_picture/{$user->image}",
                    "profile_picture/thumbnail/{$user->image}",
                ]);
            }

            // 2. Tạo tên file duy nhất
            $ext  = $request->image->extension();
            $name = "{$user->id}-" . time() . ".{$ext}";

            // 3. Lưu ảnh gốc
            $pathOriginal = $request->image->storeAs('profile_picture', $name, 'public');

            // 4. Tạo và lưu thumbnail 200×200
            $thumbnail = Image::make($request->image)
                ->fit(200, 200, function ($constraint) {
                    $constraint->upsize();
                });

            Storage::disk('public')
                ->put("profile_picture/thumbnail/{$name}", (string) $thumbnail->encode());

            // 5. Cập nhật database
            $user->update(['image' => $name]);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Profile picture updated successfully!',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Profile picture upload failed: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'errors' => ['message' => 'Không thể cập nhật ảnh đại diện, vui lòng thử lại.']
            ], 500);
        }
    }

    public function createJob()
    {
        $categories = Category::orderBy('name', 'ASC')->where('status', 1)->get();
        $jobTypes = JobType::orderBy('name', 'ASC')->where('status', 1)->get();

        return view(
            'front.account.job.create',
            compact(
                'categories',
                'jobTypes'
            )
        );
    }

    public function saveJob(Request $request)
    {
        $rules = [
            'title' => 'required|min:5|max:200',
            'category' => 'required',
            'job_type' => 'required',
            'vacancy' => 'required|integer',
            'location' => 'required',
            'description' => 'required',
            'company_name' => 'required|min:5|max:100',
            'company_location' => 'nullable',
            'company_website' => 'nullable',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes()) {
            $job = new Job();

            $job->title = $request->title;
            $job->category_id = $request->category;
            $job->job_type_id = $request->job_type;
            $job->user_id = Auth::user()->id;
            $job->vacancy = $request->vacancy;
            $job->salary = $request->salary;
            $job->location = $request->location;
            $job->description = $request->description;
            $job->benefits = $request->benefits;
            $job->responsibility = $request->responsibility;
            $job->qualifications = $request->qualifications;
            $job->keywords = $request->keywords;
            $job->experience = $request->experience;
            $job->company_name = $request->company_name;
            $job->company_location = $request->company_location;
            $job->company_website = $request->company_website;

            $job->save();

            session()->flash('success', 'Job created successfully!');

            return response()->json([
                'status' => true,
                'errors' => [],
            ]);
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ]);
        }
    }

    public function myJobs()
    {
        $jobs = Job::where('user_id', Auth::user()->id)
            ->with(['jobType', 'applications'])
            ->orderBy('created_at', 'DESC')->paginate(10);

        return view(
            'front.account.job.my-jobs',
            compact('jobs')
        );
    }

    public function editJob(Request $request, $id)
    {
        $categories = Category::orderBy('name', 'ASC')->where('status', 1)->get();
        $jobTypes = JobType::orderBy('name', 'ASC')->where('status', 1)->get();

        $job = Job::where([
            'user_id' => Auth::user()->id,
            'id' => $id,
        ])->first();

        abort_if($job == null, 404);

        return view(
            'front.account.job.edit',
            compact(
                'job',
                'jobTypes',
                'categories',
            )
        );
    }

    public function updateJob(Request $request, $id)
    {
        $rules = [
            'title' => 'required|min:5|max:200',
            'category' => 'required',
            'job_type' => 'required',
            'vacancy' => 'required|integer',
            'location' => 'required',
            'description' => 'required',
            'company_name' => 'required|min:5|max:100',
            'company_location' => 'nullable',
            'company_website' => 'nullable',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes()) {
            $job = Job::find($id);

            $job->title = $request->title;
            $job->category_id = $request->category;
            $job->job_type_id = $request->job_type;
            $job->user_id = Auth::user()->id;
            $job->vacancy = $request->vacancy;
            $job->salary = $request->salary;
            $job->location = $request->location;
            $job->description = $request->description;
            $job->benefits = $request->benefits;
            $job->responsibility = $request->responsibility;
            $job->qualifications = $request->qualifications;
            $job->keywords = $request->keywords;
            $job->experience = $request->experience;
            $job->company_name = $request->company_name;
            $job->company_location = $request->company_location;
            $job->company_website = $request->company_website;

            $job->save();

            session()->flash('success', 'Job updated successfully!');

            return response()->json([
                'status' => true,
                'errors' => [],
            ]);
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ]);
        }
    }

    public function deleteJob(Request $request)
    {
        $job = Job::where([
            'user_id' => Auth::user()->id,
            'id' => $request->id,
        ])->first();

        if ($job == null) {
            $message = 'Either job was deleted or you are not authorized to delete this job!';

            session()->flash('error', $message);

            return response()->json([
                'status' => false,
                'message' => $message,
            ]);
        }

        Job::where('id', $request->id)->delete();

        $message = 'Job deleted successfully!';
        session()->flash('success', $message);

        return response()->json([
            'status' => true,
            'message' => $message,
        ]);
    }

    public function removeJob(Request $request)
    {
        $jobApplication = JobApplication::where([
            'id' => $request->id,
            'user_id' => Auth::user()->id,
        ])->first();

        if ($jobApplication == null) {
            $message = 'Either job application was removed or you are not authorized to remove this job!';

            session()->flash('error', $message);

            return response()->json([
                'status' => false,
                'message' => $message,
            ]);
        }

        JobApplication::where('id', $request->id)->delete();

        $message = 'Job application removed successfully!';
        session()->flash('success', $message);

        return response()->json([
            'status' => true,
            'message' => $message,
        ]);
    }

    public function removeSavedJob(Request $request)
    {
        $savedJob = SavedJob::where([
            'id' => $request->id,
            'user_id' => Auth::user()->id,
        ])->first();

        if ($savedJob == null) {
            $message = 'Either job application was removed or you are not authorized to remove this job!';

            session()->flash('error', $message);

            return response()->json([
                'status' => false,
                'message' => $message,
            ]);
        }

        SavedJob::where('id', $request->id)->delete();

        $message = 'Saved job removed successfully!';
        session()->flash('success', $message);

        return response()->json([
            'status' => true,
            'message' => $message,
        ]);
    }

    public function myJobApplications()
    {
        $jobApplications = JobApplication::where(
            'user_id',
            Auth::user()->id
        )
            ->with(
                [
                    'job',
                    'job.jobType',
                    'job.jobCategory',
                    'job.applications',
                ]
            )
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        return view('front.account.job.my-job-applications', compact('jobApplications'));
    }

    public function savedJobs()
    {
        $savedJobs = SavedJob::where(
            ['user_id' => Auth::user()->id]
        )->with(
            [
                'job',
                'job.jobType',
                'job.jobCategory',
            ]
        )
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        return view('front.account.job.saved-jobs', compact('savedJobs'));
    }
}
