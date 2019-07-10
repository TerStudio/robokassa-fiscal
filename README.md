# robokassa-fiscal
Code for generationg second fiscal receipt using Robokassa API for Drupal 7
Код для генерации второго фискального чека посредством API Robokassa (Робокасса) для Drupal 7 с использованием Drupal commerce.

Код написан по приведённой здесь инструкции самой Робокассы:
https://docs.robokassa.ru/?_ga=2.260570829.1196230086.1561971708-1690336568.1464253657#7696

Изменения в 54-ФЗ вступили в силу с 1 июля 2019 года. 
В связи с переходом на новый формат передачи фискальных данных в ФНС (формат 1.05), продавец обязан пробивать ещё один чек, когда отгружает товар, оказывает услугу или выполняет работу в счёт предоплаты. 

Пример вызова:
 ```php
 $fiscal = new PaymentFiscalSecond($orderId, $tax, $paymentMethod, $paymentObject, $sno);
 
 $fiscal->collectData();
```
 
где 
$orderId - номер заказа
 
$tax - налог, один из 
    «none» – без НДС;
    «vat0» – НДС по ставке 0%;
    «vat10» – НДС чека по ставке 10%;
    «vat110» – НДС чека по расчетной ставке 10/110;
    «vat20» – НДС чека по ставке 20%; 
    «vat120» – НДС чека по расчетной ставке 20/120. 

$paymentMethod - Признак способа расчёта

$paymentObject - Признак предмета расчёта

$sno - Система налогообложения.

Подробнее о параметрах - здесь https://docs.robokassa.ru/?_ga=2.260570829.1196230086.1561971708-1690336568.1464253657#6866

Например:
 ```php
$fiscal = new PaymentFiscalSecond(1025, 'vat0', 'full_prepayment', 'service', 'usn_income_outcome');
```

#### Обратите внимание:
при формировании чека следует передавать 2 номера - один - номер заказа ($orderId), а второй - Номер заказа магазина (не должен совпадать с OriginId). Данный код писался для магазина с небольшим количеством заказов, в котором по факту этот номер - один и тот же. Поэтому второй номер мы получаем, просто прибавляя к ID 100000. 

#### Ошибки:
В случае, если статус ответ не сообщил о том, что чек сформирован, в журнал ошибок записывается сообщение.
