# Berry Symfony Bundle

Symfony bundle for the [berry/html](https://github.com/atomicptr/berry) eDSL

## Usage

Install via composer

```bash
$ composer req berry/symfony
```

Next we'll create two views one for the layout and one for the index page:

**src/View/AppLayout.php**

```php
<?php declare(strict_types=1);

namespace App\View;

use Berry\Html5\Enums\Rel;
use Berry\Element;

use function Berry\Html5\body;
use function Berry\Html5\div;
use function Berry\Html5\head;
use function Berry\Html5\html;
use function Berry\Html5\link;
use function Berry\Html5\script;
use function Berry\Html5\title;

class AppLayout
{
    public function render(string $title, Element $content): Element
    {
        return html()
            ->child(head()
                ->child(title()->text($title))
                // lets use pico.css for styling https://picocss.com
                ->child(link()
                    ->rel(Rel::Stylesheet)
                    ->href('https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css')))
            ->child(body()
                ->child(div()
                    ->class('container')
                    ->child($content))
                // also we add HTMX
                ->child(script()->src('https://cdnjs.cloudflare.com/ajax/libs/htmx/2.0.7/htmx.min.js')));
    }
}

```

**src/View/IndexPage.php**

```php
<?php declare(strict_types=1);

namespace App\View;

use Berry\Element;
use Berry\Symfony\Locator\Trait\WithGenerateUrlLocator;
use Symfony\Component\Routing\Router;

use function Berry\Html5\button;
use function Berry\Html5\div;
use function Berry\Html5\h1;
use function Berry\Html5\p;

class IndexPage
{
    // gives us access to $this->generateUrl(...)
    use WithGenerateUrlLocator;

    public function render(): Element
    {
        return div()
            ->child(h1()->text('Counter Page'))
            ->child(p()->text('Click the button to increase the counter'))
            // we add the counter button with a start value of 1
            ->child($this->counterButton(1));
    }

    // we make the button public so we can later access it from the controller
    public function counterButton(int $value): Element
    {
        return button()
            ->id('counter-button')
            // when clicked on the button increase the value by 1
            ->attr('hx-post', $this->generateUrl('app_counter', ['value' => $value + 1]))
            ->attr('hx-swap', 'outerHTML')
            ->text("+ $value");
    }
}
```

and lastly we also need to add this to a controller:

**src/Controller/IndexController.php**

```php
<?php declare(strict_types=1);

namespace App\Controller;

use App\View\AppLayout;
use App\View\IndexPage;
use Berry\Symfony\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// NOTE: This is an abstract controller from berry/symfony
class IndexController extends AbstractController
{
    // if you dont want to use our AbstractController you can alternative just add the trait
    // use BerryControllerTrait;

    public function __construct(
        private AppLayout $layout,
    ) {}

    #[Route('/', name: 'app_index', methods: ['GET'])]
    public function index(IndexPage $page): Response
    {
        // we create a page object wrapped inside our layout
        $content = $this->layout->render('Index Page', $page->render());

        // and last we just renderBerryView
        return $this->renderBerryView($content);
    }

    #[Route('/counter/{value}', name: 'app_counter', methods: ['POST'])]
    public function counter(int $value, IndexPage $page): Response
    {
        // on a POST to "/counter/{value}" we want to only render the button again
        // with an increased value so lets create the index page without layout
        $content = $page->counterButton($value);

        // and only call the counterButton
        return $this->renderBerryView($content);
    }
}
```

## License

MIT

