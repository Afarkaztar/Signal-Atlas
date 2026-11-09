@if ($errors->any())
    <div class="alert alert-danger">
        <i data-lucide="alert-octagon"></i>
        <div>
            <b>Ada {{ $errors->count() }} isian yang perlu diperbaiki:</b>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    </div>
@endif
