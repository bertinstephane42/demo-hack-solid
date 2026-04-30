<?php

namespace Core;

use RuntimeException;

class View
{
    protected string $viewPath;

    protected array $data = [];

    protected ?string $layout = null;

    protected static array $sharedData = [];

    public function __construct(string $viewPath)
    {
        $this->viewPath = rtrim($viewPath, DIRECTORY_SEPARATOR);
    }

    public static function make(string $viewPath): static
    {
        return new static($viewPath);
    }

    public function render(string $view, array $data = [], ?string $layout = null): string
    {
        $mergedData = array_merge(static::$sharedData, $this->data, $data);

        $layout = $layout ?? $this->layout;

        $content = $this->renderView($view, $mergedData);

        if ($layout !== null) {
            $mergedData['content'] = $content;
            $mergedData['slot'] = $content;

            return $this->renderView($layout, $mergedData);
        }

        return $content;
    }

    public function with(string $key, mixed $value = null): static
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    public function withData(array $data): static
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    public function layout(string $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    public function share(string $key, mixed $value): void
    {
        static::$sharedData[$key] = $value;
    }

    public function shares(array $data): void
    {
        static::$sharedData = array_merge(static::$sharedData, $data);
    }

    public function getSharedData(): array
    {
        return static::$sharedData;
    }

    public function setViewPath(string $path): void
    {
        $this->viewPath = rtrim($path, DIRECTORY_SEPARATOR);
    }

    public function getViewPath(): string
    {
        return $this->viewPath;
    }

    public function exists(string $view): bool
    {
        return file_exists($this->resolveViewPath($view));
    }

    protected function renderView(string $view, array $data): string
    {
        $path = $this->resolveViewPath($view);

        if (!file_exists($path)) {
            throw new RuntimeException("View [{$view}] not found at path [{$path}].");
        }

        $contents = file_get_contents($path);

        $contents = $this->compileDirectives($contents);

        $tempFile = sys_get_temp_dir() . '/view_' . md5($contents . microtime(true)) . '.php';

        file_put_contents($tempFile, $contents);

        ob_start();

        extract($data);

        include $tempFile;

        $output = ob_get_clean();

        if (file_exists($tempFile)) {
            @unlink($tempFile);
        }

        return $output;
    }

    protected function compileDirectives(string $contents): string
    {
        $contents = $this->compileIncludes($contents);
        $contents = $this->compileYield($contents);
        $contents = $this->compileSection($contents);
        $contents = $this->compileEndSection($contents);
        $contents = $this->compileEcho($contents);

        return $contents;
    }

    protected function compileIncludes(string $contents): string
    {
        $pattern = '/@include\s*\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,\s*\$(\w+)\s*)?\)/';

        return preg_replace_callback($pattern, function ($matches) {
            $view = $matches[1];
            $variable = $matches[2] ?? null;

            $path = $this->resolveViewPath($view);

            if (!file_exists($path)) {
                return '<?php /* Include not found: ' . $view . ' */ ?>';
            }

            $includeContents = file_get_contents($path);
            $includeContents = $this->compileDirectives($includeContents);

            if ($variable !== null) {
                return '<?php $' . $variable . ' = $' . $variable . ' ?? []; extract(array_merge(get_defined_vars(), [\'' . $variable . '\' => $' . $variable . '])); ?>'
                    . $includeContents;
            }

            return '<?php extract(get_defined_vars()); ?>' . $includeContents;
        }, $contents);
    }

    protected function compileYield(string $contents): string
    {
        $pattern = '/@yield\s*\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,\s*[\'"]([^\'"]*)[\'"]\s*)?\)/';

        return preg_replace_callback($pattern, function ($matches) {
            $section = $matches[1];
            $default = $matches[2] ?? '';

            return '<?php echo isset($__sections[\'' . $section . '\']) ? $__sections[\'' . $section . '\'] : \'' . addslashes($default) . '\'; ?>';
        }, $contents);
    }

    protected function compileSection(string $contents): string
    {
        $pattern = '/@section\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/';

        return preg_replace_callback($pattern, function ($matches) {
            $section = $matches[1];

            return '<?php ob_start(); $__current_section = \'' . $section . '\'; ?>';
        }, $contents);
    }

    protected function compileEndSection(string $contents): string
    {
        $pattern = '/@endsection/';

        return preg_replace_callback($pattern, function () {
            return '<?php $__sections[$__current_section] = ob_get_clean(); ?>';
        }, $contents);
    }

    protected function compileEcho(string $contents): string
    {
        $contents = preg_replace('/\{\{(.+?)\}\}/', '<?php echo htmlspecialchars($1, ENT_QUOTES, \'UTF-8\'); ?>', $contents);
        $contents = preg_replace('/\{!!(.+?)!!\}/', '<?php echo $1; ?>', $contents);

        return $contents;
    }

    protected function resolveViewPath(string $view): string
    {
        $view = str_replace('.', DIRECTORY_SEPARATOR, $view);

        return $this->viewPath . DIRECTORY_SEPARATOR . $view . '.php';
    }
}
