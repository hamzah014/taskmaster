@extends('layouts.app')

@section('content')
    <div class="content-wrapper-before gradient-45deg-deep-purple-purple"></div>
    <div class="breadcrumbs-inline pb-0 pt-4" id="breadcrumbs-wrapper">
        <!-- Search for small screen-->
        <div class="container">
            <div class="row">
                <div class="col s10 m6 l6 breadcrumbs-left">
                    <h5 class="breadcrumbs-title mt-0 mb-0 display-inline hide-on-small-and-down"><span>{{ __('Roles & Permissions')}}</span></h5>
                    <ol class="breadcrumbs mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home')}}</a>
                        </li>
                        <li class="breadcrumb-item active">{{ __('Roles & Permissions')}}
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="col s12">
        <div class="container">
            <div class="section">
                <div class="row">
                    <div class="col s12">
                        <div class="card">
                            <div class="card-content">
                                <div class="col m6 s6" style=" padding:5px"> <h4 class="card-title">{{ __('List of Role')}}</h4></div>
								<div class="col s6 m6 l6" style="text-align:right; padding:5px">
									<a href="#roleModal" class="new modal-trigger waves-effect waves-light btn gradient-45deg-indigo-light-blue">
										<i class="material-icons right">add_circle_outline</i> {{ __('New Role')}}</a>
								</div>
                                
                                <div id="popout" class="row">
                                    <div class="col s12">
                                        <ul class="collapsible popout">
                                            @foreach ($roles as $role)
                                                @if($role->RLCode != 'ADMIN')
													@php
														$options = [];
														$fontColor = 'black';
														$submitButton = 'yes';
													@endphp

													@if($role->RLName == 'SYSADMIN' )
														@if($user->US_RLCode != $role->RLCode && $user->RLCode != 'SYSADMIN' )
															@php
																$options = ['disabled'];
																$fontColor = 'purple';
																$submitButton = '';
															@endphp
														@endif
													@endif
                                            <li class="">
                                                <div class="collapsible-header" tabindex="0"><i class="material-icons">filter_drama</i>{{ $role->RLName }}</div>
                                                <div class="collapsible-body" style="">

                                                    <form class="ajax-form-confirm" action="{{ route('setting.rolesAndPermissions.storePermission', ['id' => $role->RLID]) }}" method="POST">
                                                    <span>
                                                        <h6>{{ __('List of Permission')}}</h6>
                                                        <div id="popout" class="row">
                                                                    <div class="col s12">
                                                                        <ul class="collapsible popout">


                                                                    @foreach($permissions as $x => $perm)
                                                                                <li class="">
                                                                                <div class="collapsible-header" tabindex="0">{{ ucfirst(str_replace("_"," ",strtoupper($x))) }}</div>
                                                                                <div class="collapsible-body">
                                                                               <div id="popout" class="row">
                                                                                                <div class="col s12">
                                                                                                    <ul class="collapsible popout">
                                                                                    @foreach($perm as $j => $k)

                                                                                        <li class="">
                                                                                            <div class="collapsible-header" tabindex="0"><i class="material-icons">filter_drama</i>{{ ucfirst(str_replace("_"," ",strtoupper($j))) }}</div>
                                                                                            <div class="collapsible-body">
                                                                                                 <div class="card" >
																									<div class="card-content">
																										<div class="row">
																											@foreach($k as $l => $m)
																												@php
																													$per_found = null;
	
																													if( isset($role) ) {
																														$per_found = $role->hasPermissionTo($role->RLCode, $m->PMCode);
																													}
																												@endphp
																												<div class="input-field col m4 s6 form-group">
																														<label>
																															{!! Form::checkbox("permissions[]", $m->PMCode, $per_found, $options) !!}
																															<span>{{ ucfirst(str_replace("_"," ",$m->PMName)) }}</span>
																														</label>
																												</div>
																											@endforeach
																											</br></br></br>
																										</div>
																									</div>
																								</div>
                                                                                            </div>
                                                                                        </li>

                                                                                        @endforeach

                                                                                        </ul>
                                                                                    </div>
                                                                                </div>
                                                                                </div>
                                                                            </li>
                                                                @endforeach
                                                                                    </ul>
                                                                    </div>
                                                                </div>
                                                                <br/>
																<div class="row">
																	<div class="col s12 m12 l12" style="text-align:right;">
																		<button type="button" class="btn btn-danger" id="delete" data-id="{{ $role->RoleID }}" data-url="{{ route('setting.rolesAndPermissions.delete') }}" style="margin-bottom: 15px; margin-left: 15px; float: left">{{ __('Delete')}}</button>
																		<a href="#roleModal" class="rename modal-trigger waves-effect waves-light btn mb-1 light-green darken-2" data-id="{{ $role->RoleID }}" data-name="{{ $role->RoleName }}" style="margin-bottom: 15px; margin-left: 15px; float: left">{{ __('Change Name')}}</a>
																		<button class="btn waves-effect waves-light cyan" id="submit" type="submit">{{ __('Submit')}}</button>
																	</div>
																</div>
                                                    </span>
                                                        </form>
                                                </div>
                                            </li>
                                        @endif
                                    @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="roleModal" class="modal modal-fixed-footer">
        <form class="ajax-form" novalidate action="{{ route('setting.rolesAndPermissions.create') }}" method="POST">
            @csrf
            <div class="modal-content">
                <h4>{{ __('Role')}}</h4>
                <div class="row">
                    {!! Form::hidden('roleID', null, ['id' => 'roleID']) !!}
					<div class="input-field col m12 s6 @if ($errors->has('roleName')) has-error @endif">
                        <i class="material-icons prefix">class</i>
                        {!! Form::text('roleName', ' ', ['id' => 'roleName', 'class' => 'form-control']) !!}
                        <label for="roleName">{{ __('Role Name')}}</label>
                        @if ($errors->has('roleName')) <p class="help-block">{{ $errors->first('roleName') }}</p> @endif
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat ">{{ __('Close')}}</a>
                <button type="submit" class="modal-action modal-close waves-effect waves-green btn-flat ">{{ __('Save')}}</button>
            </div>
        </form>
    </div>
@endsection
@push('script')
    <script>
        (function ($) {
            $(document).on("click", ".new", function () {
                var roleID = '';
                $(".modal-content #roleID").val(roleID);

                var roleName = '';
                $(".modal-content #roleName").val(roleName);
            });

            $(document).on("click", ".rename", function () {
                var roleID = $(this).data('id');
                $(".modal-content #roleID").val(roleID);

                var roleName = $(this).data('name');
                $(".modal-content #roleName").val(roleName);
            });

        })(jQuery);
    </script>
@endpush