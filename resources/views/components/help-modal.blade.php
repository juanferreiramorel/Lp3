@php
    $hasHelpTopics = $hasHelpTopics ?? false;
    $helpTopicsForRoute = $helpTopicsForRoute ?? collect();
    $firstHelpTopic = $firstHelpTopic ?? null;
    $helpTopicsCount = $helpTopicsCount ?? $helpTopicsForRoute->count();
@endphp

@if ($hasHelpTopics)
    <div class="modal fade" id="helpTopicModal" tabindex="-1" role="dialog" aria-labelledby="helpTopicModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="w-100">
                        <div class="d-flex justify-content-between align-items-start">
                            <span class="badge badge-info" id="helpTopicModalBadge">
                                <i class="fas fa-book-reader mr-1"></i>{{ $helpTopicsCount }} manual(es) disponibles
                            </span>
                            <button type="button" class="close ml-3" data-dismiss="modal" aria-label="Cerrar ayuda">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="mt-2">
                            <h5 class="modal-title d-flex align-items-center" id="helpTopicModalLabel">
                                <i class="fas fa-question-circle mr-2 text-primary"></i>
                                <span id="helpTopicModalTitleText">{{ $firstHelpTopic->title ?? 'Ayuda' }}</span>
                            </h5>
                            <p class="mb-1 text-muted small" id="helpTopicModalSubtitle">
                                {{ $firstHelpTopic->description ?? 'Selecciona un manual para visualizarlo.' }}
                            </p>
                            <p class="text-muted small mb-0" id="helpTopicModalModule">
                                @if (!empty($firstHelpTopic?->module))
                                    Modulo: <strong>{{ $firstHelpTopic->module }}</strong>
                                @else
                                    &nbsp;
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-4 mb-3 mb-lg-0">
                            <div class="list-group" id="helpTopicList" role="tablist">
                                @foreach ($helpTopicsForRoute as $topic)
                                    <button type="button"
                                            class="list-group-item list-group-item-action {{ $loop->first ? 'active' : '' }}"
                                            data-help-src="{{ route('ayuda.pdf', $topic->id_help) }}"
                                            data-help-title="{{ $topic->title }}"
                                            data-help-description="{{ e($topic->description ?? '') }}"
                                            data-help-module="{{ $topic->module }}"
                                            data-help-link="{{ route('ayuda.pdf', $topic->id_help) }}">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>{{ $topic->title }}</span>
                                            @if (!empty($topic->module))
                                                <span class="badge badge-light text-muted">{{ $topic->module }}</span>
                                            @endif
                                        </div>
                                        @if (!empty($topic->description))
                                            <small class="text-muted d-block mt-1">
                                                {{ \Illuminate\Support\Str::limit($topic->description, 80) }}
                                            </small>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="mb-3">
                                <h5 id="helpTopicViewerTitle" class="mb-1">{{ $firstHelpTopic->title ?? 'Ayuda' }}</h5>
                                <p id="helpTopicViewerDescription" class="text-muted small mb-2">
                                    {{ $firstHelpTopic->description ?? 'Selecciona un manual para ver su contenido.' }}
                                </p>
                            </div>
                            <div class="embed-responsive embed-responsive-16by9 mb-3" style="min-height: 60vh;">
                                <iframe id="helpTopicFrame" class="embed-responsive-item"
                                        src="{{ $firstHelpTopic ? route('ayuda.pdf', $firstHelpTopic->id_help) : '' }}"
                                        title="PDF de ayuda"
                                        loading="lazy">
                                </iframe>
                            </div>
                            <div class="d-flex justify-content-end">
                                <a id="helpTopicExternalLink"
                                   href="{{ $firstHelpTopic ? route('ayuda.pdf', $firstHelpTopic->id_help) : '#' }}"
                                   target="_blank" rel="noopener"
                                   class="btn btn-primary btn-sm shadow-sm"
                                   title="Abrir el PDF en una nueva pestaña">
                                    <i class="fas fa-external-link-alt mr-1"></i> Ver en nueva pestaña
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var items = document.querySelectorAll('#helpTopicList .list-group-item');
                var iframe = document.getElementById('helpTopicFrame');
                var title = document.getElementById('helpTopicViewerTitle');
                var description = document.getElementById('helpTopicViewerDescription');
                var modalTitleText = document.getElementById('helpTopicModalTitleText');
                var modalSubtitle = document.getElementById('helpTopicModalSubtitle');
                var modalModule = document.getElementById('helpTopicModalModule');
                var externalLink = document.getElementById('helpTopicExternalLink');

                if (!items.length) {
                    return;
                }

                function updateView(item) {
                    if (!item) {
                        return;
                    }

                    items.forEach(function (btn) {
                        btn.classList.remove('active');
                    });
                    item.classList.add('active');

                    var src = item.getAttribute('data-help-src');
                    var titleText = item.getAttribute('data-help-title') || 'Ayuda';
                    var descText = item.getAttribute('data-help-description') || '';
                    var moduleText = item.getAttribute('data-help-module') || '';
                    var link = item.getAttribute('data-help-link') || '#';

                    if (iframe && src) {
                        iframe.setAttribute('src', src);
                    }

                    if (title) {
                        title.textContent = titleText;
                    }

                    if (description) {
                        description.textContent = descText.trim() ? descText : 'Sin descripcion.';
                    }

                    if (modalTitleText) {
                        modalTitleText.textContent = titleText;
                    }

                    if (modalSubtitle) {
                        modalSubtitle.textContent = descText.trim() ? descText : 'Sin descripcion.';
                    }

                    if (modalModule) {
                        modalModule.innerHTML = moduleText
                            ? 'Modulo: <strong>' + moduleText + '</strong>'
                            : '&nbsp;';
                    }

                    if (externalLink) {
                        externalLink.setAttribute('href', link);
                    }
                }

                items.forEach(function (item) {
                    item.addEventListener('click', function (event) {
                        event.preventDefault();
                        updateView(item);
                    });
                });

                var active = document.querySelector('#helpTopicList .list-group-item.active') || items[0];
                updateView(active);
            });
        </script>
    @endpush
@endif
