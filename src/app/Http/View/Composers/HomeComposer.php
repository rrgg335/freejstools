<?php

namespace App\Http\View\Composers;

use Illuminate\View\View;

class HomeComposer
{
    public function compose(View $view): void
    {
        $view->with('tools', $this->getTools());
    }

    private function getTools():array
    {
        return [
            [
                'url' => route('password-generator'),
                'title' => 'Password Generator',
            ],
            [
                'url' => route('base64-encoder'),
                'title' => 'Base64 Encode/Decode',
            ]
        ];
    }
}
