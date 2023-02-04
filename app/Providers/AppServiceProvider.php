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
        
        $replaced = false;
        Mutator::data('paragraph', function ($data, $meta) use (&$replaced){
            if (empty($data->content)) {
                return;
            }
            /** remove this check and then replacing it works */
            if ($replaced){
                return;
            }
            foreach ($data->content as $i => $node) {
                if (isset($node->text)) {

                    $regex = '/^(.*?)\bspecimen\b(.*)$/';
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
                    $replaced = true;
                }
                
            }
        });
    }
}
