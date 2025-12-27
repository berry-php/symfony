# Berry Symfony Bundle

Symfony bundle for the berry/html eDSL

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
use Berry\Symfony\View\AbstractView;
use Berry\Renderable;

use function Berry\Html5\body;
use function Berry\Html5\div;
use function Berry\Html5\head;
use function Berry\Html5\html;
use function Berry\Html5\link;
use function Berry\Html5\script;
use function Berry\Html5\title;

class AppLayout extends AbstractView
{
    // we set the content we want to render inside the layout in the constructor
    public function __construct(
        private Renderable $content
    ) {}

    public function render(): Renderable
    {
        return html()
            ->child(head()
                ->child(title()->text('Index Page'))
                // lets use pico.css for styling https://picocss.com
                ->child(link()
                    ->rel(Rel::Stylesheet)
                    ->href('https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css')))
            ->child(body()
                ->child(div()
                    ->class('container')
                    ->child($this->content))
                // also we add HTMX
                ->child(script()->src('https://cdnjs.cloudflare.com/ajax/libs/htmx/2.0.7/htmx.min.js')));
    }
}

```

**src/View/IndexPage.php**

```php
<?php declare(strict_types=1);

namespace App\View;

use Berry\Symfony\View\AbstractView;
use Berry\Renderable;
use Symfony\Component\Routing\Router;

use function Berry\Html5\button;
use function Berry\Html5\div;
use function Berry\Html5\h1;
use function Berry\Html5\p;

class IndexPage extends AbstractView
{
    // here we add the router to create urls
    public function __construct(
        private Router $router
    ) {}

    public function render(): Renderable
    {
        return div()
            ->child(h1()->text('Counter Page'))
            ->child(p()->text('Click the button to increase the counter'))
            // we add the counter button with a start value of 1
            ->child($this->counterButton(1));
    }

    // we make the button public so we can later access it from the controller
    public function counterButton(int $value): Renderable
    {
        return button()
            ->id('counter-button')
            // when clicked on the button increase the value by 1
            ->attr('hx-post', $this->router->generate('app_counter', ['value' => $value + 1]))
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

    #[Route('/', name: 'app_index', methods: ['GET'])]
    public function index(): Response
    {
        // we create a page object wrapped inside our layout
        $page = new AppLayout(
            new IndexPage($this->container->get('router'))
        );

        // and last we just renderBerryView
        return $this->renderBerryView($page);
    }

    #[Route('/counter/{value}', name: 'app_counter', methods: ['POST'])]
    public function counter(int $value): Response
    {
        // on a POST to "/counter/{value}" we want to only render the button again
        // with an increased value so lets create the index page without layout
        $page = new IndexPage($this->container->get('router'));

        // and only call the counterButton
        return $this->renderBerryView($page->counterButton($value));
    }
}
```

## License

MIT

