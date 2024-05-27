
<style>

</style>

<div class="row">
    <div class="col s12">
        <span class="header-button">
            <h4 class="card-title left">Senarai Notifikasi</h4>
            <div style="text-align:right; padding:5px">
                <span id="btnAdditional">
                    <a href="#" class="new modal-trigger waves-effect waves-light btn btn-light-primary" onclick="markAsRead()">
                    <i class="material-icons left">mail</i>{{ __('Tanda sebagai dibaca')}}
                    </a>
                    <a href="#" class="new modal-trigger waves-effect waves-light btn btn-light-primary" onclick="deleteMark()">
                    <i class="material-icons left">delete</i>{{ __('Padam')}}
                    </a>
                </span>
                <a href="#" class="new modal-trigger waves-effect waves-light btn btn-light-primary" onclick="markAll()">
                <i class="material-icons left" id="toggleIcon">mail</i><span id="toggleText">{{ __('Tanda semua')}}</span>
                </a>
            </div>
        </span>
        <br/>
        <table id="table-notif" class="speed">
            <thead>
                <tr>
                    <th width="5%"></th>
                    <th>Tajuk</th>
                    <th>Tarikh</th>
                    <!-- <th>Mesej</th> -->
                </tr>
            </thead>
        </table>
    </div>
</div>
