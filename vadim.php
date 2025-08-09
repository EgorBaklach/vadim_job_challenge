<?php

# Калькулятор выходной цены из расчета наценок поставщиков

final class Definition
{
    private array $methods = [];

    public function __construct(private mixed $service){}

    public function bindExecute(string $method, ...$params): self
    {
        $this->methods[$method] = $params; return $this;
    }

    public function __invoke(): mixed
    {
        if($this->service instanceof Closure) $this->service = call_user_func($this->service);

        if(is_object($this->service)) foreach($this->methods as $method => $params) $this->service->{$method}(...$params);

        return $this->service;
    }
}

final class ServiceLocator
{
    private static array $services = [];

    public static function register(string $name, mixed $service): Definition
    {
        return self::$services[$name] ??= new Definition($service);
    }

    public static function get(string $name): mixed
    {
        if(!array_key_exists($name, self::$services)) throw new RuntimeException("Service $name is not included"); return call_user_func(self::$services[$name]);
    }
}

final class Supplier
{
    /** @var float[] */
    private array $prices = [];

    /** @var array[] */
    private array $rules = [];

    public function __construct(public readonly int $id, public readonly ?float $discountless_markup = null){}

    public function setRules(...$rules): void
    {
        foreach($rules as $rule)
        {
            [$min, $max, $markup] = array_pad($rule, 3, null);

            # Если не указано максимально допустимое значение в диапазоне
            if(is_null($markup))
            {
                $markup = $max; $max = PHP_INT_MAX;
            }

            # Проверяем значения по диапазону на тип данных
            foreach(compact('min', 'max', 'markup') as $name => $value) if(!is_int($value)) throw new TypeError(ucfirst($name)." value is not integer");

            # Выбрасываем исключение если Минимальная цена указана больше чем Максимальная
            if($min > $max) throw new LogicException("Min price must not exceed the Max price");

            $this->rules[] = [$min, $max, $markup];
        }

        # Отсортируем правила от наибольшей наценки до наименьшей DESC
        usort($this->rules, fn($p, $n) => array_pop($n) <=> array_pop($p));
    }

    public function setPrice(int $pid, $price): self
    {
        $this->prices[$pid] ??= $price; return $this;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function prices(): array
    {
        return $this->prices;
    }

    public function getPrice(int $id): float
    {
        return $this->prices[$id];
    }
}

final class Product
{
    /** @var Supplier[]  */
    private array $suppliers = [];

    public function __construct(public readonly int $id, public readonly bool $discountless = false){}

    public function invoices(array $suppliers = []): self
    {
        foreach($suppliers as $name => $price) $this->suppliers[] = ServiceLocator::get($name)->setPrice($this->id, $price); return $this;
    }

    public function suppliers(): array
    {
        return $this->suppliers;
    }

    public function prices(): array
    {
        return array_map(fn(Supplier $supplier) => ServiceLocator::get(Calculator::class)($this, $supplier), $this->suppliers);
    }
}

final class Calculator
{
    /** Наценка по умолчанию */
    private const def_markup = 25;

    public function __invoke(Product $product, Supplier $supplier): array
    {
        $price = $supplier->getPrice($product->id); $callback = fn(int $markup) => compact('markup') + ['supplier_id' => $supplier->id, 'rawPrice' => $price, 'convertPrice' => $price + $price * $markup / 100];

        # Если у товара отключен учет скидок
        if($product->discountless) return $callback($supplier->discountless_markup ?? self::def_markup);

        # Основной расчет наценки по диапазонам
        foreach($supplier->getRules() as [$min, $max, $markup]) if($price > $min && $max > $price) return $callback($markup);

        # Если цена не попала ни в один диапазон
        return $callback(self::def_markup);
    }
}

try
{
    # Описываем обращение к Конвертеру цен

    ServiceLocator::register(Calculator::class, fn() => new Calculator());

    # Создаем пул Поставщиков

    # * Поставщик с Вашими диапазонами
    ServiceLocator::register('supplier_1', fn() => new Supplier(1, 50))->bindExecute('setRules', [1000, 5000, 30], [0, 500, 45], [10000, 20], [5000, 8000, 25]);

    # * Старые поставщики
    ServiceLocator::register('supplier_2', fn() => new Supplier(2, 30))->bindExecute('setRules', [0, 499, 45], [500, 999, 40], [1000, 4999, 35]);
    ServiceLocator::register('supplier_3', fn() => new Supplier(3))->bindExecute('setRules', [0, 999, 35], [1000, 9999, 30]);
    ServiceLocator::register('supplier_4', fn() => new Supplier(4));

    # Описываем создание товара с входящими ценами поставщиков

    ServiceLocator::register('product_101', fn() => new Product(101))->bindExecute('invoices', ['supplier_1' => 1200, 'supplier_2' => 1050, 'supplier_3' => 1010, 'supplier_4' => 600]);

    print_r(ServiceLocator::get('supplier_1')->getRules());

    /* Array (
        [0] => Array (
            [0] => 0
            [1] => 500
            [2] => 45
        )
        [1] => Array (
            [0] => 1000
            [1] => 5000
            [2] => 30
        )
        [2] => Array (
            [0] => 5000
            [1] => 8000
            [2] => 25
        )
        [3] => Array (
            [0] => 10000
            [1] => 9223372036854775807
            [2] => 20
        )
    )*/

    print_r(ServiceLocator::get('product_101')->prices());

    /** Array(
        [0] => Array (
            [markup] => 30
            [supplier_id] => 1
            [rawPrice] => 1200
            [convertPrice] => 1560
        )
        [1] => Array (
            [markup] => 35
            [supplier_id] => 2
            [rawPrice] => 1050
            [convertPrice] => 1417.5
        )
        [2] => Array (
            [markup] => 30
            [supplier_id] => 3
            [rawPrice] => 1010
            [convertPrice] => 1313
        )
        [3] => Array (
            [markup] => 25
            [supplier_id] => 4
            [rawPrice] => 600
            [convertPrice] => 750
        )
    )*/
}
catch (Throwable $e)
{
    die($e);
}

# SQL запрос

try
{
    $pdo = new PDO('mysql:host=localhost;dbname=vadim;charset=utf8mb4', 'root', 'pass', [
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_ERRMODE  => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_PERSISTENT => false,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
    ]);

    $values = [
        'section' => ['Concept car', PDO::PARAM_STR],
        'manufacturer' => ['Hot Wheels', PDO::PARAM_STR],
        'scale' => ['1:64', PDO::PARAM_STR]
    ];

    $statement = $pdo->prepare("SELECT `products`.`id` as `id`, `products`.`name` as `name` FROM `products`
    
    # Таблица `ps` содержит записи \"многие ко многим\" - чтобы можно было указать несколько категорий для одного товара и несколько товаров для одной категории,
    # При этом чтобы сохранить целостность у таблицы установлен составной уникальный индекс UNIQUE (`pid`,`sid`)  
    
    JOIN `ps` on `products`.`id`=`ps`.`pid`
    JOIN `sections` on `ps`.`sid`=`sections`.`id`
    
    # Для таблиц масштабов и произовителей связь \"один к одному\" - поэтому в таблице товаров есть связующие поля `manufacturer_id` и `scale_id` 
        
    JOIN `manufacturers` on `products`.`manufacturer_id`=`manufacturers`.`id`
    JOIN `scales` on `products`.`scale_id`=`scales`.`id`
    
    WHERE `sections`.`name` LIKE :section AND `manufacturers`.`name` LIKE :manufacturer AND `scales`.`value` LIKE :scale;");

    foreach($values as $field => [$value, $type]) $statement->bindParam($field, $value, $type);

    $statement->execute();

    print_r($statement->fetchAll());

    /** Array (
        [0] => Array (
            [id] => 1
            [name] => Baja Truck No.33, orange
        )
    )*/
}
catch (Throwable $e)
{

}

