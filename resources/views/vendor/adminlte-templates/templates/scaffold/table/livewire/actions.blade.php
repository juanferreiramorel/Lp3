<div class='btn-group'>
    <a href="{{ $showUrl }}" class='btn btn-default btn-xs'>
        <i class="fa fa-eye"></i>
    </a>
    <a href="{{ $editUrl }}" class='btn btn-default btn-xs'>
        <i class="fa fa-edit"></i>
    </a>
    <button type="button" class='btn btn-danger btn-xs' onclick="handleLivewireDelete({{ $recordId }}, this)">
        <i class="fa fa-trash"></i>
    </button>
</div>
