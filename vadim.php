<?php

# Калькулятор выходной цены из расчета наценок поставщиков.

final class ServiceLocator
{
    private static array $services = [];

    public static function register(string $name, mixed $service): void
    {
        self::$services[$name] ??= $service;
    }

    public static function get(string $name): mixed
    {
        if(!array_key_exists($name, self::$services)) throw new RuntimeException("Service $name is not included");

        if(self::$services[$name] instanceof Closure) self::$services[$name] = call_user_func(self::$services[$name]);

        return self::$services[$name];
    }
}

final class Supplier
{
    /** @var float[] */
    private array $prices = [];

    public function __construct(public readonly int $id, public readonly array $rules = [], public readonly ?float $discountless_markup = null){}

    public function __invoke(Product $product, $price): self
    {
        $this->prices[$product->id] ??= $price; return $this;
    }

    public function prices(): array
    {
        return $this->prices;
    }

    public function price(int $id): float
    {
        return $this->prices[$id];
    }
}

final class Product
{
    /** @var Supplier[]  */
    private array $suppliers = [];

    public function __construct(public readonly int $id, public readonly bool $discountless = false){}

    public function __invoke(array $suppliers = []): self
    {
        foreach($suppliers as $name => $price) $this->suppliers[] = ServiceLocator::get($name)($this, $price); return $this;
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
    private const def_markup = 25;

    public function __invoke(Product $product, Supplier $supplier): array
    {
        $price = $supplier->price($product->id); $callback = fn(int $markup) => compact('markup') + ['rawPrice' => $price, 'convertPrice' => $price + $price * $markup / 100];

        if($product->discountless) return $callback($supplier->discountless_markup ?? self::def_markup);

        foreach($supplier->rules as $max => $value) if($max >= $price) return $callback($value);

        return $callback(self::def_markup);
    }
}

try
{
    # Описываем обращение к Конвертеру цен

    ServiceLocator::register(Calculator::class, fn() => new Calculator());

    # Создаем пул Поставщиков

    ServiceLocator::register('supplier_1', fn() => new Supplier(1, [499 => 45, 999 => 40, 4999 => 35], 50));
    ServiceLocator::register('supplier_2', fn() => new Supplier(2, [999 => 30, 9999 => 25]));
    ServiceLocator::register('supplier_3', fn() => new Supplier(3));

    # Описываем создание товара с входящими ценами поставщиков

    ServiceLocator::register('product_101', fn() => call_user_func(new Product(101), ['supplier_1' => 450, 'supplier_2' => 1000, 'supplier_3' => 600]));
    ServiceLocator::register('product_102', fn() => call_user_func(new Product(102, true), ['supplier_1' => 600, 'supplier_3' => 800]));

    # Выводим цены поставщиков для товаров 101 и 102

    print_r(ServiceLocator::get('product_101')->prices());

    /** Array (
        [0] => Array (
            [markup] => 45
            [rawPrice] => 450
            [convertPrice] => 652.5
        )
        [1] => Array (
            [markup] => 25
            [rawPrice] => 1000
            [convertPrice] => 1250
        )
        [2] => Array (
            [markup] => 25
            [rawPrice] => 600
            [convertPrice] => 750
        )
    )*/

    print_r(ServiceLocator::get('product_102')->prices());

    /** Array (
        [0] => Array (
            [markup] => 50
            [rawPrice] => 600
            [convertPrice] => 900
        )
        [1] => Array (
            [markup] => 25
            [rawPrice] => 800
            [convertPrice] => 1000
        )
    )*/
}
catch (Throwable $e)
{
    echo "Ошибка: " . $e->getMessage();
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

