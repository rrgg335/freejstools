<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;
use Exception;

class Publish extends Command
{
    protected $signature = 'app:publish';

    protected $description = 'Publishes all route files as html files, assets, sitemap etc';

    private $outputPath;

    private $baseUrl;

    public function __construct()
    {
        parent::__construct();
        $this->outputPath = base_path('../docs');
        $this->baseUrl = config('app.url');
    }

    public function handle()
    {
        $this->clearDirectory();
        $this->buildHTML();
        $this->buildAssets();
        $this->buildCNAMEFile();
        $this->buildRobotsFile();
        $this->buildSitemapsFile();
    }

    private function clearDirectory(): void {
        $this->info('Clearing output directory...');
        if (!is_dir($this->outputPath)) {
            \Illuminate\Support\Facades\File::makeDirectory($this->outputPath, 0755, true);
            $this->info('✓ Created output directory: ' . $this->outputPath);
            return;
        }
        \Illuminate\Support\Facades\File::cleanDirectory($this->outputPath);
        $this->info('✓ Output directory cleared: ' . $this->outputPath);
    }
    
    private function buildHTML(): void {
        $this->info('Building HTML files...');
        $kernel = app(Kernel::class);
        foreach ($this->getFilteredRoutes() as $route) {
            $uri = $route->uri();
            $request = Request::create('/' . ltrim($uri, '/'), 'GET');
            try {
                $response = $kernel->handle($request);
                if ($response->getStatusCode() === 200) {
                    $content = preg_replace([
                        '/<!--(.|\s)*?-->/',
                        '/\s+/',
                        '/>\s+</',
                    ],[
                        '',
                        ' ',
                        '><',
                    ], $response->getContent());
                    $filePath = $uri === '/' ? 'index.html' : $uri . '/index.html';
                    $fullPath = $this->outputPath . '/' . $filePath;
                    File::ensureDirectoryExists(dirname($fullPath));
                    File::put($fullPath, $content);
                    $this->info('✓ '.$uri.': file built');
                }else{
                    $this->error('✗ '.$uri.': HTTP '.$response->getStatusCode());
                }
            } catch (Exception $e) {
                $this->error('✗ '.$uri.': file failed to build'.PHP_EOL.$e->getMessage());
            }
        }
    }
    
    private function buildAssets(): void {
        $this->info('Building Assets...');
        $process = new Process(['npm', 'run', 'build']);
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(300);

        $process->run(function ($type, $buffer) {
            // echo $buffer;
        });

        if ($process->isSuccessful()) {
            $this->info('✓ Assets published to '.$this->outputPath.'/assets');
        } else {
            $this->error('✗ Asset build failed!');
        }
    }
    
    private function buildCNAMEFile(): void {
        $this->info('Building CNAME file...');
        
        File::put($this->outputPath . '/CNAME', strtr($this->baseUrl,[
            'https://' => '',
            'http://' => '',
        ]));
        $this->info('✓ CNAME file published to '.$this->outputPath.'/CNAME');
    }
    
    private function buildRobotsFile(): void {
        $this->info('Building robots.txt file...');
        $content = implode(PHP_EOL,[
            'User-agent: *',
            'Disallow:',
            '',
            'Sitemap: '.$this->baseUrl.'/sitemap.xml',
        ]);
        
        File::put($this->outputPath . '/robots.txt', $content);
        $this->info('✓ robots.txt file published to '.$this->outputPath.'/robots.txt');
    }
    
    private function buildSitemapsFile(): void {
        $this->info('Building sitemaps...');
        $content = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];
        
        foreach ($this->getFilteredRoutes() as $route) {
            $content[] = '  <url>';
            $content[] = '      <loc>'.rtrim($this->baseUrl.'/'.$route->uri(), '/').'</loc>';
            $content[] = '      <lastmod>'.now()->toAtomString().'</lastmod>';
            $content[] = '      <changefreq>weekly</changefreq>';
            $content[] = '      <priority>0.8</priority>';
            $content[] = '  </url>';
        }
        
        $content[] = '</urlset>';
        $xml = implode(PHP_EOL,$content);
        
        File::put($this->outputPath . '/sitemap.xml', $xml);
        $this->info('✓ Sitemaps published to '.$this->outputPath.'/sitemap.xml');
    }

    private function getFilteredRoutes(): Collection {
        return collect(Route::getRoutes())->filter(function ($route) {
            return in_array('GET', $route->methods()) && !preg_match('/\{.+\}/', $route->uri()) && !str_starts_with($route->uri(), 'storage');
        });
    }
}