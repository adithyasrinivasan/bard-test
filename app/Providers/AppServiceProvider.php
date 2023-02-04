<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Statamic\Statamic;
use Statamic\Facades\GlobalSet;
use Statamic\Fieldtypes\Section;
use JackSleight\StatamicBardMutator\Facades\Mutator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            \Tiptap\Editor::class,
            \JackSleight\StatamicBardMutator\Editor::class
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Statamic::script('app', 'cp');
        // Statamic::style('app', 'cp');

        Section::makeSelectableInForms();

        View::composer(['layout', 'errors/404'], function ($view) {
            if ($view['response_code'] == '404') {
                $entry = GlobalSet::find('configuration')->inCurrentSite()->error_404_entry;
                if(!$entry) {
                    $entry = GlobalSet::find('configuration')->inDefaultSite()->error_404_entry;
                }
                $view->with($entry->toAugmentedArray());
            }
        });

        Mutator::data('paragraph', function ($data, $meta){
            if (empty($data->content)) {
                return;
            }
            foreach ($data->content as $i => $node) {
               
                if ($node->type !== 'text') {
                    continue;
                }

                foreach ($node->marks ?? [] as $mark){
                    if ($mark->type !== 'italic' && $mark->type !== 'bold' && $mark->type !== 'underline'){
                        continue;
                    }
                }

                $regex = '/^(.*?)\bLorem ipsum\b(.*)$/';

                if (!preg_match($regex, $node->text, $match)) {
                    continue;
                }
                
                [, $before, $after] = $match;
                array_splice($data->content, $i, 1, [
                    (object)['type' => 'text', 'text' => $before],
                    (object)['type' => 'text', 'text' => 'DUMMY TEXT', 'marks' => [
                        (object)['type' => 'link', 'attrs' => (object)['href' => 'https://google.com']],
                    ]],
                    (object)['type' => 'text', 'text' => $after],
                ]);
                
                return;
            }
        });
    }
}
