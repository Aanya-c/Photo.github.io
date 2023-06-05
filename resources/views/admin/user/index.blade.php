@extends('layouts.app')
@if (isset($page_title) && $page_title != '')
    @section('title', $page_title . ' | ' . config('app.name'))
@else
    @section('title', config('app.name'))
@endif
@section('page-style')
    <!-- DataTables -->
    <link href="{{ asset('assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet"
        type="text/css" />
    <link href="{{ asset('assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css') }}" rel="stylesheet"
        type="text/css" />
    <!-- Responsive datatable examples -->
    <link href="{{ asset('assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}"
        rel="stylesheet" type="text/css" />
@endsection
@section('breadcrumb')
    @include('layouts.includes.breadcrumb')
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="DataTable" class="table table-bordered dt-responsive nowrap w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Role</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('page-script')
    <!-- Required datatable js -->
    <script src="{{ asset('assets/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <!-- Buttons examples -->
    <script src="{{ asset('assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-buttons-bs4/js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/libs/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('assets/libs/pdfmake/build/pdfmake.min.js') }}"></script>
    <script src="{{ asset('assets/libs/pdfmake/build/vfs_fonts.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-buttons/js/buttons.colVis.min.js') }}"></script>
    <!-- Responsive examples -->
    <script src="{{ asset('assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>
    <script type="text/javascript">
        var table;
        $(document).ready(function() {
            table = $('#DataTable').DataTable({
                processing: true,
                serverSide: true,
                searching: true,
                pageLength: 50,
                ajax: {
                    headers: {
                        'X-CSRF-Token': Laravel.csrfToken
                    },
                    url: "{!! route('admin.user.data') !!}",
                    type: 'POST',
                    dataType: "json",
                    beforeSend: function() {
                        if (typeof table != 'undefined' && table.hasOwnProperty('settings')) {
                            table.settings()[0].jqXHR.abort();
                        }
                    }
                },
                columns: [{
                        data: "DT_RowIndex",
                        name: "id"
                    },
                    {
                        data: 'role_name',
                        name: 'roles.display_name',
                        sortable: false
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: "status",
                        name: "status",
                        "render": function(data, type, row) {
                            if (data == 1) {
                                return '<button type="button" class="btn btn-outline-success btn-sm change-status-btn" data-id="' +
                                    row.id + '" data-status="0">Active</button>';
                            } else {
                                return '<button type="button" class="btn btn-outline-danger btn-sm change-status-btn" data-id="' +
                                    row.id + '" data-status="1">InActive</button>';
                            }
                        }
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        sortable: false
                    }
                ],
                order: [
                    [0, 'desc']
                ]
            });
            table.on('click', '.change-status-btn', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                var status = $(this).data('status');
                if ((typeof id !== "undefined") && (typeof status !== "undefined")) {
                    swal({
                        title: 'Are You Sure you want to change status?',
                        type: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Do it!',
                        confirmButtonColor: "#3085d6",
                        cancelButtonText: 'No, cancel!',
                        cancelButtonColor: '#d33',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.value) {
                            $.ajax({
                                type: "POST",
                                url: "{!! route('admin.user.status.change') !!}",
                                data: {
                                    id: id,
                                    status: status
                                },
                                dataType: 'json',
                                headers: {
                                    'X-CSRF-TOKEN': Laravel.csrfToken
                                }
                            }).done(function(response) {
                                if (response.success == true) {
                                    setTimeout(function() {
                                        redrawDatatable();
                                    }, 1000);
                                    swal({
                                        type: 'success',
                                        text: response.message,
                                        timer: '1500'
                                    });
                                } else {
                                    swal({
                                        type: 'error',
                                        text: response.message,
                                        timer: '500000'
                                    });
                                }
                            }).fail(function(jqXHR, status, exception) {
                                if (jqXHR.status === 0) {
                                    error =
                                        'Not connected.\nPlease verify your network connection.';
                                } else if (jqXHR.status == 404) {
                                    error = 'The requested page not found. [404]';
                                } else if (jqXHR.status == 500) {
                                    error = 'Internal Server Error [500].';
                                } else if (exception === 'parsererror') {
                                    error = 'Requested JSON parse failed.';
                                } else if (exception === 'timeout') {
                                    error = 'Time out error.';
                                } else if (exception === 'abort') {
                                    error = 'Ajax request aborted.';
                                } else {
                                    error = 'Uncaught Error.\n' + jqXHR.responseText;
                                }
                                Swal.fire('Error!', error, 'error');
                            });
                        }
                    });
                }
            });
        });

        function deleteRecord(ele, id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                type: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                confirmButtonColor: "#d33",
                confirmButtonClass: 'btn btn-success',
                cancelButtonText: 'No, cancel!',
                cancelButtonColor: '#3085d6',
                cancelButtonClass: 'btn btn-danger',
                buttonsStyling: true,
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        type: "POST",
                        headers: {
                            'X-CSRF-Token': Laravel.csrfToken
                        },
                        url: "{!! route('admin.user.destroy') !!}",
                        dataType: 'json',
                        data: {
                            id: id
                        },
                        success: function(response) {
                            setTimeout(function() {
                                redrawDatatable();
                            }, 1000);
                            if (response.success == true) {
                                swal({
                                    type: 'success',
                                    title: 'Deleted!',
                                    text: response.message,
                                    timer: '1500'
                                });
                            } else {
                                swal({
                                    type: 'error',
                                    title: 'Not Deleted!',
                                    text: response.message,
                                    timer: '1500'
                                });
                            }
                        },
                        error: function(jqXHR, status, exception) {
                            if (jqXHR.status === 0) {
                                error = 'Not connected.\nPlease verify your network connection.';
                            } else if (jqXHR.status == 404) {
                                error = 'The requested page not found. [404]';
                            } else if (jqXHR.status == 500) {
                                error = 'Internal Server Error [500].';
                            } else if (exception === 'parsererror') {
                                error = 'Requested JSON parse failed.';
                            } else if (exception === 'timeout') {
                                error = 'Time out error.';
                            } else if (exception === 'abort') {
                                error = 'Ajax request aborted.';
                            } else {
                                error = 'Uncaught Error.\n' + jqXHR.responseText;
                            }
                            Swal.fire('Error!', error, 'error');
                        }
                    });
                }
            });
        }

        function redrawDatatable() {
            $('#DataTable').DataTable().ajax.reload();
        }
    </script>
@endsection
