@php($section = setting('projects_section', []))
<section class="projects">
    <div class="projects-grid">
        <div class="projects-info">
            <p class="sub-title reveal">{{ $section['subtitle'] ?? '' }}</p>
            <h2 class="projects-title reveal">{{ $section['title'] ?? '' }}</h2>
            <p class="reveal">{{ $section['text'] ?? '' }}</p>
            <div class="proj-nav reveal">
                <span class="proj-count"><b id="projCurrent">01</b> / <span id="projTotal">{{ str_pad($projects->count(), 2, '0', STR_PAD_LEFT) }}</span></span>
                <button class="proj-prev" aria-label="Previous"><i class="fa-solid fa-arrow-left-long"></i></button>
                <button class="proj-next" aria-label="Next"><i class="fa-solid fa-arrow-right-long"></i></button>
            </div>
            <a href="{{ link_to_url($section['button_url'] ?? '#') }}" class="btn btn-gradient reveal">{{ $section['button_text'] ?? 'All Projects' }} <i class="fa-solid fa-arrow-right"></i></a>
        </div>

        <div class="swiper proj-swiper reveal" data-anim="fade-left">
            <div class="swiper-wrapper">
                @foreach ($projects as $project)
                    <div class="swiper-slide proj-card">
                        <img src="{{ media($project->image) }}" alt="{{ $project->title }}" loading="lazy">
                        <div class="proj-meta">
                            <span>{{ $project->category }}</span>
                            <h4>{{ $project->title }}</h4>
                            <a href="{{ $project->url ?: '#' }}" class="proj-plus" aria-label="View project">+</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
