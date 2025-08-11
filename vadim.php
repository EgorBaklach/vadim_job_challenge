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

/**
 * @property int $min
 * @property int|null $max
 * @property int $markup
 */
final class Rule
{
    private int $min;
    private ?int $max;
    private ?int $markup;

    public function __construct(public int $id, ...$rule)
    {
        [$this->min, $this->max, $this->markup] = array_pad($rule, 3, null);

        # Если не указано максимально допустимое значение в диапазоне
        if(is_null($this->markup))
        {
            $this->markup = $this->max; $this->max = null;
        }

        if($this->max > 0 && $this->min > $this->max) throw new LogicException("Min price must not exceed Max price");
    }

    public function __get(string $name): mixed
    {
        return $this->{$name};
    }
}

final class Supplier
{
    /** @var float[] */
    private array $prices = [];

    /** @var Rule[] */
    private array $rules = [];

    public function __construct(public readonly int $id, public readonly ?float $discountless_markup = null){}

    public function setRules(...$rules): void
    {
        foreach($rules as $rule) $this->rules[] = ServiceLocator::get($rule);
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
        foreach($supplier->getRules() as $rule)
        {
            if($price > $rule->min) # Первый шаг входа - Если цена больше минимально установленной стоимости в диапазоне
            {
                if($rule->max > 0 && $price >= $rule->max) continue; # Пропускаем - Если максимальная цена установлена, но цена товара больше

                return $callback($rule->markup); # Когда все совпало - цена товара находится в диапазоне. Она больше минимальной и меньше максимальной, либо максимальная цена не указана
            }
        }

        # Если цена не попала ни в один диапазон
        return $callback(self::def_markup);
    }
}

try
{
    # Описываем обращение к Конвертеру цен

    ServiceLocator::register(Calculator::class, fn() => new Calculator());

    # Создаем пул Правил наценок - Диапазонов цен

    ServiceLocator::register('rule_1', fn() => new Rule(1, 1000, 5000, 30)); // От 1000 до 5000 наценка = 30%
    ServiceLocator::register('rule_2', fn() => new Rule(2, 0, 500, 45)); // От 0 до 500 наценка = 45%
    ServiceLocator::register('rule_3', fn() => new Rule(3, 10000, 20)); // От 10000 и до максимально допустимого INT = 20%
    ServiceLocator::register('rule_4', fn() => new Rule(4, 5000, 8000, 25)); // От 5000 до 8000 = 25%

    ServiceLocator::register('rule_5', fn() => new Rule(5, 0, 499, 45));
    ServiceLocator::register('rule_6', fn() => new Rule(6, 500, 999, 40));

    ServiceLocator::register('rule_7', fn() => new Rule(7, 0, 999, 35));

    # Создаем пул Поставщиков

    # * Поставщик с Вашими диапазонами
    ServiceLocator::register('supplier_1', fn() => new Supplier(1, 50))->bindExecute('setRules', 'rule_1', 'rule_2', 'rule_3', 'rule_4');

    # * Старые поставщики
    ServiceLocator::register('supplier_2', fn() => new Supplier(2, 30))->bindExecute('setRules', 'rule_5', 'rule_6', 'rule_1');
    ServiceLocator::register('supplier_3', fn() => new Supplier(3))->bindExecute('setRules', 'rule_7');
    ServiceLocator::register('supplier_4', fn() => new Supplier(4));

    # Описываем создание товара с входящими ценами поставщиков

    ServiceLocator::register('product_101', fn() => new Product(101))->bindExecute('invoices', ['supplier_1' => 1200, 'supplier_2' => 1050, 'supplier_3' => 1010, 'supplier_4' => 600]);

    print_r(ServiceLocator::get('supplier_1')->getRules());

    /* Array (
        [0] => Rule Object (
            [min:Rule:private] => 1000
            [max:Rule:private] => 5000
            [markup:Rule:private] => 30
            [id] => 1
        )
        [1] => Rule Object (
            [min:Rule:private] => 0
            [max:Rule:private] => 500
            [markup:Rule:private] => 45
            [id] => 2
        )
        [2] => Rule Object (
            [min:Rule:private] => 10000
            [max:Rule:private] =>
            [markup:Rule:private] => 20
            [id] => 3
        )
        [3] => Rule Object (
            [min:Rule:private] => 5000
            [max:Rule:private] => 8000
            [markup:Rule:private] => 25
            [id] => 4
        )
    )*/

    print_r(ServiceLocator::get('product_101')->prices());

    /* Array (
        [0] => Array (
            [markup] => 30
            [supplier_id] => 1
            [rawPrice] => 1200
            [convertPrice] => 1560
        )
        [1] => Array (
            [markup] => 30
            [supplier_id] => 2
            [rawPrice] => 1050
            [convertPrice] => 1365
        )
        [2] => Array (
            [markup] => 25
            [supplier_id] => 3
            [rawPrice] => 1010
            [convertPrice] => 1262.5
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

    # Пример запроса на вывод данных по одному товару

    $values = [
        'section' => ['Concept car', PDO::PARAM_STR],
        'manufacturer' => ['Hot Wheels', PDO::PARAM_STR],
        'scale' => ['1:64', PDO::PARAM_STR]
    ];

    $statement = $pdo->prepare("SELECT 
    
    `products`.`id` as `id`,  
    `products`.`name` as `name`,
    
    `sections`.`name` as `section`, # наименование раздела
    `manufacturers`.`name` as `manufacturer`, # наименование производителя
    `scales`.`value` as `scale` # площадь
    
    FROM `products`
    
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
            [section] => Concept car
            [manufacturer] => Hot Wheels
            [scale] => 1:64
        )
    )*/

    # Пример запроса на вывод нескольких разделов по одному товару: "Baja Truck No.33%"

    $name = 'Baja Truck No.33%';

    $statement = $pdo->prepare("SELECT 
    
    `products`.`id` as `id`,  
    `products`.`name` as `name`,
    
    GROUP_CONCAT(`sections`.`name`  SEPARATOR ', ') AS `sections`
    
    FROM `products`
    
    JOIN `ps` on `products`.`id`=`ps`.`pid`
    JOIN `sections` on `ps`.`sid`=`sections`.`id`
    
    WHERE `products`.`name` LIKE :name
    
    GROUP BY `products`.`id`;");

    $statement->bindParam('name', $name);

    $statement->execute();

    print_r($statement->fetchAll());

    /** Array (
        [0] => Array (
            [id] => 1
            [name] => Baja Truck No.33, orange
            [sections] => Concept car, Автоспорт
        )
    )*/
}
catch (Throwable $e)
{

}

