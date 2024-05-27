

<div class="card">
    <div class="card-header card-header-stretch min-height-none">
        <div class="card-toolbar m-0">
            <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-6 border-0 fw-bold" role="tablist">
                <li class="nav-item" role="presentation">
                    <a id="req_tab" class="nav-link justify-content-center text-active-gray-800 active" data-bs-toggle="tab" role="tab" href="#req_content">
                        <span class="badge badge-dark me-2">1</span> Requirement Analysis
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a id="scoring_tab" class="nav-link justify-content-center text-active-gray-800" data-bs-toggle="tab" role="tab" href="#scoring_content">
                        <span class="badge badge-dark me-2">2</span> Requirement Scoring
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div id="req_content" class="card-body p-0 tab-pane fade show active" role="tabpanel" aria-labelledby="req_content">
                <div class="w-100">

                    <div id="ideaInfo">
                        <div class="fv-row text-center">
                            <h4 class="">Let's analyse the requirement</h4>
                            <br>
                            <h5>Idea written by : {{ $projectIdea->user->USName }}</h5>
                        </div>

                        <div class="fv-row mb-8 mt-5">
                            <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                <span class="">Project Idea</span>
                            </label>
                            <input type="text" class="form-control" id="projectIdea" name="projectIdea" placeholder="Enter project idea" value='" {{ $projectIdea->PIDesc }} "' readonly>
                        </div>
                        <hr class="mb-8">
                        <div class="fv-row mb-8">
                            <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                <span class="">1. User story statement</span>
                            </label>
                            <span>As a <input value="{{ $projectIdea->PIPersona }}" type="text" class="form-control w-25 d-inline" id="persona" name="persona" placeholder="<persona>" readonly> ,</span>
                            <span>I can <input value="{{ $projectIdea->PIAction }}" type="text" class="form-control w-25 d-inline" id="doAction" name="doAction" placeholder="<do something>" readonly> </span>
                            <span>so that <input value="{{ $projectIdea->PIImpact }}" type="text" class="form-control w-35 d-inline" id="impact" name="impact" placeholder="<impact/priority/value>" readonly> </span>
                        </div>
                        <div class="fv-row mb-8">
                            <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                <span class="">2. Additional expectations of the idea.</span>
                            </label>
                            <textarea id="description" name="description" class="form-control" readonly
                            data-kt-autosize="true" placeholder="Tell about the desired flow and outcomes">{{ $projectIdea->PI_ReqDesc }}</textarea>
                        </div>

                        @if ($projectIdea->fileAttach)
                        <div class="fv-row mb-8">
                            <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                <span class="">3. Supplementary Diagram or Sketch.</span>
                            </label>
                            <a target="_blank" href="{{ route('file.view', $projectIdea->fileAttach->FAGuidID) }}" class="btn btn-info btn-sm">View</a>
                        </div>
                        @endif
                    </div>

                </div>
            </div>
            <div id="scoring_content" class="card-body p-0 tab-pane fade" role="tabpanel" aria-labelledby="scoring_content">
                <div class="w-100">

                    <div id="scoreInfo">
                        <div class="fv-row mb-8 mt-5">
                            <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                <span class="">Based on the information given by {{ $projectIdea->user->USName }}, please determine the following:</span>
                            </label>
                        </div>
                        <div class="fv-row mb-8">
                            <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                <span class="">1. Name of Requirement</span>
                            </label>
                            <input readonly type="text" class="form-control" id="requirementName" name="requirementName" placeholder="Enter requirement name" value='{{ $ideaScoring ? $ideaScoring->PIS_ReqName : null }}'>
                        </div>
                        <div class="fv-row mb-8">
                            <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                <span class="">2. Type of Requirement</span>
                            </label>
                            {!! Form::select('requirementType', $requirementType , $ideaScoring ? $ideaScoring->PIS_ReqType : null , [
                                'id' => 'requirementType',
                                'class' => 'form-select form-control',
                                'placeholder' => 'Choose type',
                                'readonly'
                            ]) !!}
                        </div>
                        <div class="fv-row mb-8">
                            <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                <span class="">3. Details (if applicable)</span>
                            </label>
                            <textarea id="requirementDesc" name="requirementDesc" class="form-control" readonly
                            data-kt-autosize="true" placeholder="Detailed explaination, if given.">{{ $ideaScoring ? $ideaScoring->PIS_ReqDesc : null }}</textarea>
                        </div>
                        <div class="fv-row mb-8">
                            <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                <span class="">4. Rate the importance (1 to 100)</span>
                            </label>
                            <input type="number" class="form-control" id="requirementRate" name="requirementRate" max="100" min="1"
                            placeholder="Enter rate importance" value='{{ $ideaScoring ? $ideaScoring->PISRate : null }}' readonly>
                        </div>
                        <div class="fv-row mb-8">
                            <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                <span class="">5. Type of Importance</span>
                            </label>
                            {!! Form::select('moscowType', $moscowType , $ideaScoring ? $ideaScoring->PIS_MoscowType : null , [
                                'id' => 'moscowType',
                                'class' => 'form-select form-control',
                                'placeholder' => 'Choose type',
                                'readonly'
                            ]) !!}
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
