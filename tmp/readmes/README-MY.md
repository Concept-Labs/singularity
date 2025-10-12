# Singularity DI Next-Gen PSR-11 Container
### The Core of the Concept Ecosystem

## Table of contents
* [Overview](#Overview)
* [Базовий приклад](#base-example)
* [Configuration](#configuration)
* [Factories](#factories)


* [Instatiation](#instantiation)
    * [Plugin system](#plugin-system)

<a id="Overview"></a>
`Singularity DI` – це _PSR-11_ сумісний контейнер залежностей наступного покоління, який керує створенням сервісів з урахуванням контексту їх використання. Архітектура побудована навколо кількох ключових компонентів:

Контейнер Singularity – головний клас `Singularity` реалізує інтерфейс `PSR-11 ContainerInterface` та розширює його методи. Він надає методи `get()` для отримання сервісу та `create()` для примусового створення нового екземпляру (ігноруючи кеш). Контейнер містить посилання на конфігурацію, реєстр сервісів, менеджер плагінів та збирач контексту. При ініціалізації він приймає об’єкт конфігурації (з пакету [Concept-Labs/Config](https://github.com/Concept-Labs/config)) і налаштовує плагіни відповідно до конфігураційних даних.

Реєстр сервісів (`ServiceRegistry`) – відповідає за зберігання створених сервісів та їх повторне використання. Реєстр зберігає об’єкти за ідентифікатором і підтримує як звичайні, так і «слабкі» посилання (`WeakReference`). «Слабка» реєстрація (`$weak = true`) означає, що контейнер не перешкоджатиме збору сервісу сміттєзбирачем, якщо на нього більше немає посилань. Це дозволяє уникнути витоків пам’яті для кешованих сервісів, які більше не використовуються. Реєстр також особливо обробляє сервіси з прототипним життєвим циклом: якщо об’єкт реалізує `PrototypeInterface`, то при запиті контейнер повертає новий екземпляр шляхом виклику його методу `prototype()`. Таким чином один закешований об’єкт може слугувати прототипом для створення копій на кожен запит.

Будівник контексту (`ContextBuilder`) – компонент, що на основі поточного контексту залежностей та глобальної конфігурації формує прото-контекст сервісу (об’єкт `ProtoContext`). Контекст включає всю необхідну мета-інформацію про сервіс: його унікальний `ID`, кінцевий клас для інстанціювання, бажані аргументи конструктора, пов’язані плагіни тощо. `ContextBuilder` звертається до конфігурації (JSON-вузол `singularity`) і послідовно об’єднує preferences (налаштування прив’язки інтерфейсів до класів та параметрів) з кількох рівнів: глобального, рівня простору імен, пакету і конкретного сервісу. Для кожного сервісу в стеку залежностей ContextBuilder визначає всі відповідні налаштування:

`Preferences` пакету: налаштування, оголошені пакунком (через файл `concept.json`). Вони можуть включати вимоги до інших пакетів та типові прив’язки для класів цього пакету. Наприклад, `Singularity` у своєму `concept.json` декларує, що інтерфейс контейнера `Psr\Container\ContainerInterface` та власний `SingularityInterface` задовольняються класом `Concept\Singularity\Singularity`.

`Preferences` простору імен: конфігурація, що стосується всіх класів певного неймспейсу (визначена у вузлі `namespace`). Якщо сервіс належить цьому неймспейсу, такі налаштування додаються.

Локальні налаштування сервісу: конкретні прив’язки для запрошуваного ідентифікатора сервісу, які мають найвищий пріоритет. Наприклад, це можуть бути альтернативний клас для інтерфейсу або задані аргументи конструктора.

`ContextBuilder` об’єднує всі ці дані, визначає кінцевий клас для створення сервісу (`serviceClass`) і формує `ProtoContext`. Якщо для запитаного ідентифікатора не знайдено жодного співставлення (`service` не сконфігурований), контейнер помітить його як `unresolved` – у цьому випадку вважається, що сам ідентифікатор є іменем класу, і контейнер спробує створити його напряму через автовпровадження (`autowiring`). Якщо клас не існує – генерується виняток `ServiceNotFoundException`.

Прото-контекст сервісу (`ProtoContext`) – структура, що інкапсулює дані про створюваний сервіс. Вона містить `ID` сервісу, визначений клас для інстанціювання, стек залежностей (шлях, по якому контейнер прийшов до цього сервісу), а також конфігурацію цього сервісу (аргументи, плагіни тощо). `ProtoContext` надає методи для роботи з рефлексією: воно кешує `ReflectionClass` відповідного класу сервісу, параметри конструктора і атрибути PHP, якщо такі присутні. Крім того, прото-контекст збирає список плагінів, які потрібно застосувати до сервісу, об’єднуючи конфігураційні плагіни та плагіни, задані атрибутами у класі сервісу. За допомогою `ProtoContext` контейнер знає, який фабричний метод використати для створення об’єкту, які додаткові дії виконати до або після створення, і як поводитись з отриманим екземпляром (кешувати чи ні).

Менеджер плагінів (`PluginManager`) – відповідає за реєстрацію та виклик плагінів контейнера – додаткових обробників, які перехоплюють процес створення сервісів. Плагіни можуть виконувати довільні дії до створення об’єкта (`before`) або після його створення (`after`). Менеджер завантажує перелік глобальних плагінів із конфігурації (`singularity.settings.plugin-manager.plugins`) при ініціалізації контейнера. До глобального списку додаються також плагіни, специфічні для даного сервісу (з `ProtoContext`) – на основі цього формується черга виклику плагінів. Плагіни впорядковуються за пріоритетом (`priority`), заданим у конфігурації. При створенні сервісу контейнер спочатку викликає `PluginManager->before($context)` – це проходить по черзі плагінів і запускає їх у режимі `before`, передаючи контекст сервісу. Далі відбувається безпосередня інстанціація об’єкта, і після цього – виклик `PluginManager->after($object, $context)`, що запускає всі плагіни у режимі after, отримуючи доступ до щойно створеного сервісу. Менеджер плагінів враховує налаштування: плагін може бути відключено (значення `false` в конфігурації) або його виконання може бути умовно зупинено самим контекстом (плагін має можливість позначити, що подальші плагіни не потрібні).

Як видно, `Singularity` значно складніший за мінімальний `PSR-11` контейнер. Він не просто зберігає фабрики для інстанціювання, а підтримує динамічне визначення залежностей на основі контексту, використовуючи конфігураційне дерево, рефлексію і систему плагінів. Це дозволяє гнучко налаштовувати поведінку DI-контейнера без зміни коду сервісів – через конфігурацію або атрибути.

<a id="base-example"></a>
## Знайомство на прикладі high-level [конфігурації](#configuration) для composer пакету `foo/lifestyle`
### Створимо абстракції, реалізації та за допомогою конфігурацію співставимо їх.
_в цьому прикладі ми будемо мати на увазі вже створений `Singularity DI`(**$singularity**) екземпляр контейнеру, та використаємо його як анти-паттерн для простоти наведення прикладу. В подальшому ми перейдемо до більш "живих" прикладів концепції застосунку в екосистемі. наразі такий приклад необхідний для розуміння основи контейнеру_
* `composer.json`
```json
{
    "name": "foo/lifestyle",
    ...
    "extra": {
        "concept": {} //for Concept-Labs Ecosystem enable auto-discovery + preserved for future options
    }
}
```

`Foo\Lifestyle\TripInterface.php`
```php
namespace Foo\Lifestyle;

interface TripInterface
{
    public function getCar(): CarInterface;
}
```
`Foo\Lifestyle\AbstractTrip.php`
```php
namespace Foo\Lifestyle;

abstract class AbstractTrip implements TripInterface
{
    public function __construct(private CarInterface $car) {}

    public function getCar(): CarInterface
    {
        return $this->car;
    }
}
```

* `Foo\Lifestyle\Weekend\Trip.php`
```php
namespace Foo\Lifestyle\Weekend;

class Trip extends \Foo\Lifestyle\AbstractTrip
{
    //...
}
```
* `Foo\Lifestyle\Garage\CarInterface.php`
```php
namespace Foo\Lifestyle\Garage;

interface CarInterface
{
    public function getModel(): string;
}
```
* `Foo\Lifestyle\Garage\BMW.php`
```php
namespace Foo\Lifestyle\Garage;

class BMW implements CarInterface
{
    public function getModel(): string { return 'BMW'; }
}
```
* `Foo\Lifestyle\Garage\CarInterface.php`
```php
namespace Foo\Lifestyle\Garage;

class Audi implements CarInterface
{
    public function getModel(): string { return 'Audi'; }
}
```
* Configuration (f.e. `concept.json`)
```json
"singularity": {
    "preference": {
        "Foo\\Lifestyle\\TripInterface": {
            "class": "Foo\\Lifestyle\\Weekend\\Trip"
        },
        "Foo\\Lifestyle\\Garage\\CarInterface": {
            "class": "Foo\\Lifestyle\\Garage\\BMW"
        }
    }
}
```
### Використовуючи явний доступ до контейнеру отриимаємо:
```php
/**
 * @var Foo\Lifestyle\Weekend\Trip $trip
 */
$trip = $singularity->get(\Foo\Lifestyle\Weekend\TripInterface::class);
/**
 * @var Foo\Lifestyle\Garage\CarInterface $car
 */
$car = $trip->getCar(); // Foo\Lifestyle\Garage\BMW
$model = $car->getModel(); // "BMW"
```

* Or reconfigure to:

```json
"singularity": {
    "preference": {
        ...
        "Foo\\Lifestyle\\Garage\\CarInterface": {
            "class": "Foo\\Lifestyle\\Garage\\Audi"
        }
    }
}
```
### тоді отримаємо:
```php
/**
 * @var Foo\Lifestyle\Weekend\Trip $trip
 */
$trip = $singularity->get(\Foo\Lifestyle\Weekend\TripInterface::class);
/**
 * @var Foo\Lifestyle\Garage\CarInterface $car
 */
$car = $trip->getCar(); // Foo\Lifestyle\Garage\Audi
$model = $car->getModel(); // "Audi"
```


### Тобто ми бачимо що в залежності від конфігурації ми отримуємо різні реалізації абстракції `CarInterface`.

### (забігаючи наперед) Рухаючись "вгору" архітектури застосунку легко уявити, наприклад, наступну реалізацію  `\Psr\Http\Server\RequestHandlerInterface`:

```php
namespace Foo\Lifestyle\Weekend\Http\TripHandler;

use Foo\Lifestyle\TripInterface;

class HandleWeekendTripRequest implements \Psr\Http\Server\RequestHandlerInterface
{
    public function __construct(
        ...
        private TripInterface $trip
        ...
    )
    {
        /**
         * Singularity Container resolving and injecting $trip instance 
         * with its resolved dependencies based on the configuration
         */
    }

    protected function getTrip(): TripInterface
    {
        return $this->trip;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $carModel = $this->getTrip()->getCar()->getModel(); //BMW or Audi depends on configuration
        ...
    }
}
```
<a id="deeper-namespace"></a>

# Digging deeper (Context beggining)

### Що якщо ми хочемо в різних сервісах (`Foo\Lifestyle\Weekend\Trip` та `Foo\Lifestyle\Workday\Trip`) використовувати різні реалізації `CarInteface`? адже, використовуючи top-level ноду `preference` ми можемо співставити абстракцію та реалізацію тільки один раз. 

## Є можливість використати ноду [конфігурації](configuration) `namespace` (Не рекомендовано)
Додамо до прикладу ще одну реалізацію. Даний приклад не рекомендований але потребує бути розглянутим для повноти розуміння.
```php
namespace Foo\Lifestyle\Workday;
class Trip extends \Foo\Lifestyle\AbstractTrip
{}
```
видалимо з попереднього прикладу конфігурації `preference` для `Foo\Lifestyle\Garage\CarInterface` та використаємо ноду `namespace`: визначимо реалізації для простору імен.
```json
"singularity": {
    ...
    "namespace": {
        "Foo\\Lifestyle\\Weekend\\": {
            "preference": {
                "Foo\\Lifestyle\\Garage\\CarInterface": {
                    "class": "Foo\\Lifestyle\\Garage\\BMW"
                }
            }
        },
        "Foo\\Lifestyle\\Workday\\": {
            "preference": {
                "Foo\\Lifestyle\\Garage\\CarInterface": {
                    "class": "Foo\\Lifestyle\\Garage\\Audi"
                }
            }
        }
    }
}
```
### Легко бачити що: 
* сервіс `\Foo\Lifestyle\Weekend\Trip` має отримати екземпляр `\Foo\Lifestyle\Garage\`**BMW**
* а сервіс `\Foo\Lifestyle\Workday\Trip` має отримати екземпляр `\Foo\Lifestyle\Garage\`**Audi**


>_оскільки збирач конфігурації [concept-labs/config](https://github.com/Concept-Labs/config) містить "на борту" 
плагін аналізу composer-пакетів то нода "namespace" буде створена автоматично і не потребує додаткових дій розробника.
Приклад ілюструє вже "зібрану" конфігурацію для розуміння логіки.
нода "namespace" використовується для прив`язки php namespace до пакету а не навпаки як у composer, що пришвидшує резолюцію_

<a id="configuration"></a>
# Структура конфігурації ([Schema](singularity.schema.json)):

```json
"singularity": {
    "preference": {
        //Top level preference has most higher priority
        //These bindings will override namespace-level and package-level configuration
        "Foo\\Lifestyle\\Garage\\CarInterface": { //service ID
            "class": "Vendor\\Foo\\Garage\\<Car>", //concrete implementation
            "arguments": { //optional arguments
                "<constructor named argument>": "...", //exact value
                "<constructor named argument>": { //service (object)
                    "type": "service",
                    "preference": "<Service ID>" //Service preference (ID or exact class)
                }
                //...
            },
            "plugins": { //Optional instantiation plugins
                "<plugin>": false|mixed 
            },
            "reference": "<path>" //Reference to predefined configuration
        },
        //...More top-level bindings
    },

    //Namespace-level configuration. 
    //Entry point for package-level configuration
    //In opposite to composer-style configuration where package-level configuration is "on top"
    //we are starting to resolve services based on its namespace and detect package which may contain requested service
    "namespace": { //Container start to resolve services based on its namespace (more deeper - more concrete)
        "Foo\\Lifestyle\\Garage\\": {
            "require": { // composer-like (but not only) package (roups) dependencies
                "vendor/foo": {}, 
                //...Another dependencies
            }
        },
        "preference": {
            //Preference overrides for the whole namespace. 
            //Has higher priority than package-level
            //So we can overrride bindings for whole namespace
            "ServiceID(Abstraction)": {
                //service resolution options e.g. concrete class, agruments
                //in Concept ecosystem it is recomended to use interface-based IDs
            }
          
        }
    },
    //Package-level configuration (recomended to be used)
    "package": {
        "foo/lifestyle": {
            "require": { // package dependecies. usually built by config discovery of composer packages
                "<vendor/dependency>": {}
            }
        },
        "vendor/foo": {
            "preference": {
                "Foo\\Lifestyle\\Garage\\CarInterface": {
                    "class": "Foo\\Lifestyle\\Garage\\<Car>"
                },
                //...Continue preference configuration
            }
        }
    }
}
```

## Пакетна конфігурація



в даному прикладі ми бачимо що node "preference" була перенесена в ноду singularity->package.
Це "ізолює" дану конфігурацію від глобальної, що дає нам змогу скофігурувати вибір реалізації сервісу контейнером в залежності від пакету (де точка входу є namespace).


<a id="factories"></a>
## Standard Factories
Оскільки Singularity DIC явно не використовується на протязі життя застосунку, та для уникнення антипатерну `Service-Locator` маємо стандартизовані фабрики для створення сервісів коли це необхідно, які можуть бути розширені та інжектовані.
Будемо мати на увазі що ми маємо інжектовану стандартну фабрику `\Concept\Singularity\Factory\Factory $factory`, та будемо використовувати її в наступних прикладах, поки не перейдемо до інших патернів використання `Singularity`

### Така фабрика має вигляд:
```php
namespace Concept\Singularity\Factory;

interface FactoryInterface
{
    /**
     * Create a service instance
     * 
     * @param string $serviceId The service identifier
     * @param array $args The arguments to pass to the service
     * 
     * @return object The service instance
     */
    public function create(string $serviceId, array $args = []);
}
```
### Та спеціалізована абстрактна фабрика
```php
namespace Concept\Singularity\Factory;

interface ServiceFactoryInterface
{
    /**
     * Create a service
     * 
     * @param array $args
     * 
     * @return object
     */
    public function create(array $args = []): object;
}
```
Що має базову абстрактну реалізацію:
```php
<?php
namespace Concept\Singularity\Factory;


use Concept\Singularity\Context\ProtoContextInterface;
use Concept\Singularity\Contract\Lifecycle\SharedInterface;
use Concept\Singularity\SingularityInterface;

abstract class ServiceFactory implements ServiceFactoryInterface, SharedInterface
{

    public function __construct(
        private readonly SingularityInterface $container,
        private readonly ProtoContextInterface $context
    )
    {}

    /**
     * Create service
     * 
     * @param string $serviceId
     * @param array $args
     * 
     * @return object
     */
    protected function createService(string $serviceId, array $args = []): object
    {
        $depStack = $this->getContext()->getDependencyStack();
        array_unshift($depStack, static::class);
        
        return $this->getContainer()
            ->create(
                $serviceId,
                $args,
                $depStack
            );
    }

    /**
     * Get service manager
     * 
     * @return SingularityInterface
     */
    protected function getContainer(): SingularityInterface
    {
        return $this->container;
    }

    /**
     * Get context
     * 
     * @return ProtoContextInterface
     */
    protected function getContext(): ProtoContextInterface
    {
        return $this->context;
    }
    
}
```
** (_Бажано розуміти_) 
Якщо звернути увагу на параметер конструктору `private ProtoContextInterface $context`:
Це екземпляр поточного контексту в якому дана фабрика була створена, що дає можливість `Singularity DI` використати "заморожений" `Dependency Stack` при створенні фабрики що дає можливість створювати сервіси в даному контексті.

 Дає змогу реалізовувати власні стандартні фабрики сервісів:
 ```php
 namespace Foo\Lifecycle\Weekend;

 class TripFactory extends \Concept\Singularity\Factory\ServiceFactory
 {
    /**
     * {@inheritDoc}
     */
    public function create(array $args = []): object
    {
        return $this->createService(\Foo\Lifestyle\Weekend\Trip::class, $args);
    }
 }
 ```
 Тоді використання прикладу вище трохи зміниться
 ```php
 /**
  * Тут ми отримаємо явно фабрику, але в рамках застосунка це має бти ін'єкцією
  */
 $tripFactory = $singularity->get(Foo\\Lifestyle\\Weekend\\TripFactory::class);

 $trip = $tripFactory->create();
 $trip->drive(); ////"Driving <Car>"
 ```
















