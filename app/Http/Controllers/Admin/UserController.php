<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Spatie\Permission\Models\Role;
use App\Models\User;
use DataTables;
use Validator;
use Session;
use Image;
use Auth;
use Hash;
use File;

class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:user-list', ['only' => ['index', 'show']]);
        $this->middleware('permission:user-add', ['only' => ['create', 'store']]);
        $this->middleware('permission:user-edit', ['only' => ['edit', 'store']]);
        $this->middleware('permission:user-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $data                       = [];
            $data['page_title']         = 'User List';
            if (Auth::user()->can('user-add')) {
                $data['btnadd'][]       = array(
                    'link'  => route('admin.user.add'),
                    'title' => 'Add User'
                );
            }
            $data['breadcrumb'][]       = array(
                'link'  => route('admin.home'),
                'title' => 'Dashboard'
            );
            $data['breadcrumb'][]       = array(
                'title' => 'List'
            );
            return view('admin.user.index', $data);
        } catch (\Exception $e) {
            return abort(404);
        }
    }

    public function datatable(Request $request)
    {
        //$user = User::query()->with('roles')->whereDoesntHave('roles', function($q){ $q->where('name','customer'); });
        $user = User::query();
        return DataTables::eloquent($user)
            ->addColumn('action', function ($user) {
                $action      = '';
                if (Auth::user()->can('user-edit')) {
                    $action .= '<a data-toggle="tooltip" data-placement="top" title="Edit" data-original-title="Edit" class="btn btn-outline-secondary btn-sm" href="' . route("admin.user.edit", $user->id) . '"><i class="fas fa-pencil-alt"></i></a>&nbsp;';
                }
                if (Auth::user()->can('user-delete')) {
                    $action .= '<a data-toggle="tooltip" data-placement="top" title="Delete" data-original-title="Delete" class="btn btn-outline-danger btn-sm" href="javascript:void(0);" id="user_id_' . $user->id . '" data-id="' . $user->id . '" onclick="deleteRecord(this,' . $user->id . ');"><i class="fas fa-trash"></i></a>&nbsp;';
                }
                return $action;
            })
            ->addColumn('role_name', function ($user) {
                $role_name = '<ul>';
                foreach ($user->roles as $role) {
                    $role_name .= '<li>' . $role->display_name . '</li>';
                }
                $role_name .= '<ul>';
                return $role_name;
            })
            ->filterColumn('role_name', function ($query, $keyword) {
                $query->whereHas('roles', function ($q) use ($keyword) {
                    $q->where('dislpay_name', 'LIKE', "{$keyword}%");
                });
            })
            ->filterColumn('status', function ($query, $keyword) {
                if (strstr(strtolower("active"), strtolower($keyword)) !== false) {
                    $query->whereRaw("users.status = 1");
                } elseif (strstr(strtolower("inactive"), strtolower($keyword)) !== false) {
                    $query->whereRaw("users.status = 0");
                }
            })
            ->editColumn('created_at', function ($user) {
                return ($user->created_at) ? date('d-m-Y', strtotime($user->created_at)) : '';
            })
            ->filterColumn('created_at', function ($query, $keyword) {
                $query->whereRaw("DATE_FORMAT(users.created_at,'%d-%m-%Y') like ?", ["%$keyword%"]);
            })
            ->rawColumns(['role_name', 'action'])->addIndexColumn()
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        try {
            $data                       = [];
            $data['page_title']         = 'Add User';
            $data['breadcrumb'][]       = array(
                'link'      => route('admin.home'),
                'title'     => 'Dashboard'
            );
            if (Auth::user()->can('user-list')) {
                $data['breadcrumb'][]   = array(
                    'link'  => route('admin.user.index'),
                    'title' => 'User'
                );
            }
            $data['breadcrumb'][]       = array(
                'title' => 'Add'
            );
            $data['roles']              = Role::whereNotIn('name', ['super-admin'])->whereNull('deleted_at')->get();
            return view('admin.user.add', $data);
        } catch (\Exception $e) {
            dd($e);
            return abort(404);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function exists(Request $request)
    {
        $userId     = ($request->id) ? $request->id : '';
        if ($userId != '') {
            $result = User::where('id', '!=', $userId)->where('email', $request->email)->count();
        } else {
            $result = User::where('email', $request->email)->count();
        }
        if ($result > 0) {
            return response()->json(false);
        } else {
            return response()->json(true);
        }
    }

    public function store(Request $request)
    {
        try {
            $userId   = ($request->id) ? $request->id : '';
            $role     = Role::where('id', $request->role_id)->whereNull('deleted_at')->first();
            $rules    = [
                'role_id'               => 'required',
                'name'                  => 'required|string|max:255',
                'status'                => 'required'
            ];
            if ($userId != '') {
                $rules['email']         = 'required|string|email|max:255|unique:users,email,' . $userId;
                $rules['password']      = 'confirmed';
            } else {
                $rules['email']         = 'required|string|email|max:255|unique:users';
                $rules['password']      = 'required|string|min:8|confirmed';
            }
            $messages = [
                'role_id.required'      => 'The role field is required.',
                'name.required'         => 'The fullname field is required.',
                'email.required'        => 'The email field is required.',
                'password.required'     => 'The password field is required.',
                'status.required'       => 'The status field is required.'
            ];
            $validator      = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                if ($userId != '') {
                    return redirect()->back()
                        ->withErrors($validator)
                        ->withInput();
                } else {
                    return redirect()->back()
                        ->withErrors($validator)
                        ->withInput();
                }
            } else {
                $formdata                   = $request->all();
                unset($formdata['_token']);
                unset($formdata['id']);
                unset($formdata['role_id']);
                unset($formdata['password_confirmation']);
                if ($userId != '') {
                    $item                           = User::find($userId);
                    foreach ($formdata as $fieldName => $fieldValue) {
                        if (!$request->hasFile($fieldName)) {
                            if ($fieldName == 'password') {
                                if ($fieldValue != '') {
                                    $item->password = bcrypt($fieldValue);
                                }
                            } else {
                                $item->$fieldName   = $fieldValue;
                            }
                        }
                    }
                    $item->updated_at               = date("Y-m-d H:i:s");
                    $item->updated_by               = Auth::id();
                    if ($item->save()) {
                        $item->syncRoles($role->name);
                        Session::flash('alert-message', 'User updated successfully.');
                        Session::flash('alert-class', 'success');
                        return redirect()->route('admin.user.index');
                    } else {
                        Session::flash('alert-message', 'User updated unsuccessfully.');
                        Session::flash('alert-class', 'error');
                        return redirect()->route('admin.user.edit', $userId);
                    }
                } else {
                    $item                           = new User();
                    $formdata['email_verified_at']  = date("Y-m-d H:i:s");
                    $formdata['password']           = bcrypt($request->password);
                    $formdata['created_at']         = date("Y-m-d H:i:s");
                    $formdata['created_by']         = Auth::id();
                    if ($item = $item->create($formdata)) {
                        $item->assignRole($role->name);
                        Session::flash('alert-message', 'User added successfully.');
                        Session::flash('alert-class', 'success');
                        return redirect()->route('admin.user.index');
                    } else {
                        Session::flash('alert-message', 'User added unsuccessfully.');
                        Session::flash('alert-class', 'error');
                        return redirect()->route('admin.user.add');
                    }
                }
            }
        } catch (\Exception $e) {
            Session::flash('alert-message', $e->getMessage());
            Session::flash('alert-class', 'error');
            return redirect()->route('admin.user.index');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $data                       = [];
            $data['page_title']         = 'View User';
            $data['breadcrumb'][]       = array(
                'link'      => route('admin.dashboard'),
                'title'     => 'Dashboard'
            );
            if (Auth::user()->can('user-list')) {
                $data['breadcrumb'][]   = array(
                    'link'  => route('admin.user.index'),
                    'title' => 'User'
                );
            }
            $data['breadcrumb'][]       = array(
                'title' => 'View'
            );
            $user                       = User::where('id', $id)->first();
            if ($user) {
                $data['user']           = $user;
                return view('admin.user.view', $data);
            } else {
                return abort(404);
            }
        } catch (\Exception $e) {
            return abort(404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $data                       = [];
            $data['page_title']         = 'Edit User';
            $data['breadcrumb'][]       = array(
                'link'      => route('admin.home'),
                'title'     => 'Dashboard'
            );
            if (Auth::user()->can('user-list')) {
                $data['breadcrumb'][]   = array(
                    'link'  => route('admin.user.index'),
                    'title' => 'User'
                );
            }
            $data['breadcrumb'][]       = array(
                'title' => 'Edit'
            );
            $user                       = User::where('id', $id)->first();
            if ($user) {
                if (!(in_array('super-admin', $user->getRoleNames()->toArray()))) {
                    $data['roles']      = Role::whereNotIn('name', ['super-admin'])->whereNull('deleted_at')->get();
                } else {
                    $data['roles']      = Role::where('name', 'super-admin')->whereNull('deleted_at')->limit(1)->get();
                }
                $data['user']           = $user;
                return view('admin.user.edit', $data);
            } else {
                return abort(404);
            }
        } catch (\Exception $e) {
            return abort(404);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        if ($request->ajax()) {
            try {
                $user = User::where('id', $request->id)->first();
                if (!is_null($user)) {
                    if (!(in_array('super-admin', $user->getRoleNames()->toArray()))) {
                        if (Auth::id() != $user->id) {
                            $user->disableLogging();
                            $user->deleted_by           = Auth::id();
                            $user->save();
                            $user->enableLogging();
                            if ($user->delete()) {
                                $response['success']    = true;
                                $response['message']    = "User deleted successfully.";
                            } else {
                                $response['success']    = false;
                                $response['message']    = "User deleted unsuccessfully.";
                            }
                        } else {
                            $response['success']        = false;
                            $response['message']        = "Can't delete user because " . $user->name . " is active.";
                        }
                    } else {
                        $response['success']            = false;
                        $response['message']            = "Can't delete user because " . $user->name . " is super admin.";
                    }
                } else {
                    $response['success']                = false;
                    $response['message']                = "User record not found.";
                }
            } catch (\Exception $e) {
                $response['success']                    = false;
                $response['message']                    = $e->getMessage();
            }
            return response()->json($response);
        } else {
            return abort(404);
        }
    }

    /**
     * Update the specified user status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function statusChange(Request $request)
    {
        if ($request->ajax()) {
            try {
                $user = User::where('id', $request->id)->first();
                if (!is_null($user)) {
                    if (!(in_array('super-admin', $user->getRoleNames()->toArray()))) {
                        if (Auth::id() != $user->id) {
                            $user->status               = $request->status;
                            $user->updated_by           = Auth::id();
                            $user->updated_at           = date("Y-m-d H:i:s");
                            if ($user->save()) {
                                $response['success']    = true;
                                $response['message']    = "Status has been changed successfully.";
                            } else {
                                $response['success']    = false;
                                $response['message']    = "Status has been changed unsuccessfully.";
                            }
                        } else {
                            $response['success']        = false;
                            $response['message']        = "Can't change status user because " . $user->name . " is active.";
                        }
                    } else {
                        $response['success']            = false;
                        $response['message']            = "Can't change status user because " . $user->name . " is super admin.";
                    }
                } else {
                    $response['success']                = false;
                    $response['message']                = "Oops! Something went wrong..";
                }
            } catch (\Exception $e) {
                $response['success']                    = false;
                $response['message']                    = $e->getMessage();
            }
            return response()->json($response);
        } else {
            return abort(404);
        }
    }
}
