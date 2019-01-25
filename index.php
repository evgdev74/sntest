<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <script
            src="https://code.jquery.com/jquery-3.3.1.min.js"
            integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
            crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mustache.js/3.0.1/mustache.js"></script>
</head>
<body>
<div class="container">
<?
$url = 'http://sknt.ru/job/frontend/data.json';
$json = file_get_contents($url);
$data = json_decode($json);
if ($data->result == 'ok'):
    foreach ($data->tarifs as $id=>$item) :
        $tarif_title = isset($item->title) ? 'Тариф "' . $item->title . '"' : 'Тариф';
        $prices = [];
        $options = isset($item->free_options) ? $item->free_options : [];
        $speed = $item->speed;
        $link = $item->link;
        switch ($item->title) {
            case 'Земля':
                $color = 'brown';
                break;
            case 'Вода':
            case 'Вода HD':
                $color = 'blue';
                break;
            case 'Огонь':
            case 'Огонь HD':
            default:
                $color = 'orange';
                break;
        }
        foreach ($item->tarifs as $tarif) {
            if ($tarif->pay_period > 1) {
                $prices[] = $tarif->price/$tarif->pay_period;
            } else {
                $prices[] = $tarif->price;
            }
        }
        ?>
        <article class="item">
            <div class="item__block">
                <h3><? echo $tarif_title ?></h3>
            </div>
            <div class="item__block item__block_next">
                <div class="info">
                    <span class="info__speed info__speed_<? echo $color ?>"><? echo $speed ?> МБит/с</span><br>
                    <p class="info__price"><? echo min($prices) ?> - <? echo max($prices) ?> ₽/мес</p>
                    <?
                    if (count($options)): ?>
                        <ul class="info__options">
                            <? foreach($options as $option): ?>
                                <li><? echo $option ?></li>
                            <? endforeach; ?>
                        </ul>
                    <? endif; ?>
                </div>
                <div class="next_step">
                    <input type="hidden" name="tarif_id" value="<? echo $id ?>">
                    <button class="next_step__button" onclick="nextStep(event)"></button>
                </div>
            </div>
            <div class="item__block">
                <a href="<? echo $link ?>">Узнать подробнее на сайте www.sknt.ru</a>
            </div>
        </article>
    <? endforeach; ?>
<? endif;?>
</div>
<script>
    <? $json_for_js = substr(str_replace(array('\"ТВ\"', '\"Социальный ТВ\"'), array('\\\'\\\'ТВ\\\'\\\'', '\\\'\\\'Социальный ТВ\\\'\\\''), $json), 0, -1);?>
    var data = JSON.parse('<? echo  $json_for_js?>')
    var screen = 1
</script>
<script>
    function getBack(event) {
        (function ($) {
            if (screen == 2) {
                $('.topmenu').remove()
                $('.item.variant').remove()
                $('.item').show()
                screen = 1
            }
            if (screen == 3) {
                $('.item.confirm').remove()
                $('.topmenu.confirm').remove()
                $('.item.variant').show()
                $('.topmenu.variant').show()
                screen = 2
            }
        })(jQuery)
    }
    function nextStep(event) {
        (function($) {
            event.preventDefault()
            var tarif_index = $($(event.path[0])).siblings("input[name=tarif_id]").val()
            var tarif = data.tarifs[tarif_index]
            var variants = tarif.tarifs
            switch (screen) {
                case 1:
                    var variants_data = []
                    var max_price = 0
                function compare(a,b) {
                    if (a.price < b.price)
                        return -1;
                    if (a.price > b.price)
                        return 1;
                    return 0;
                }
                variants.sort(compare);
                    variants.forEach(function(item, i, variants) {
                        if (item.pay_period == 1) {
                            max_price = item.price
                        }
                    })
                    variants.forEach(function(item, i, variants) {
                        var_data = {
                            title: item.title,
                            price: item.price / item.pay_period,
                            opts: [{
                                option: 'Pазовый платеж - ' + item.price + '₽'
                            }],
                            id: tarif_index,
                            v_id: item.ID
                        }
                        if (item.pay_period > 1) {
                            var_data.opts.push({option: 'Скидка - ' + (max_price * item.pay_period - item.price) + '₽'})
                        }
                        variants_data[i] = var_data
                    })
                    var menu_template = $('#top_menu_template').html();
                    var template = $('#tarif_variant_template').html();
                    $('.item').hide()
                    var output = Mustache.render(menu_template, {title: tarif.title, mode: 'variant'}) + Mustache.render(template, {variants_data: variants_data});
                    $('.container').html($('.container').html() + output)
                    screen = 2
                    break;
                case 2:
                    var variant_id = $($(event.path[0])).siblings("input[name=variant_id]").val()
                    var variant_data = {}
                    variants.forEach(function(item, i, variants) {

                        var date = new Date(item.new_payday.split('+')[0] * 1000)
                        var date_formatted = +date.getMonth() > 8 ? '' + date.getDate() + '.' + (date.getMonth() + 1 ) + '.' + date.getFullYear() : '' + date.getDate() + '.0' + (date.getMonth() + 1 ) + '.' + date.getFullYear()
                        if (item.ID == variant_id) {
                            var var_data = {
                                title: item.title,
                                pay_period: item.pay_period,
                                price: item.price,
                                price_per_month: item.price/item.pay_period,
                                date: date_formatted
                            }
                            variant_data = var_data
                        }
                    })
                    var menu_template = $('#top_menu_template').html();
                    var template = $('#tarif_confirm_template').html();
                    $('.item').hide()
                    $('.topmenu.variant').hide()
                    var output = Mustache.render(menu_template, {title: 'Выбор тарифа', mode: 'confirm'}) + Mustache.render(template, variant_data);
                    $('.container').html($('.container').html() + output)
                    screen = 3
                    break;
            }
        })(jQuery);
    }
</script>
</body>
<script id="top_menu_template" type="text/html">
<div class="topmenu {{mode}}">
    <a href="#" class="topmenu__back" onclick="getBack(event)"></a>
    <h2>{{title}}</h2>
</div>
</script>
<script id="tarif_variant_template" type="text/html">
    {{#variants_data}}
    <article class="item variant">
        <div class="item__block">
            <h3>{{title}}</h3>
        </div>
        <div class="item__block item__block_next">
            <div class="info">
                <p class="info__price">{{price}} ₽/мес</p>
                <ul class="info__options">
                    {{#opts}}
                        <li>{{option}}</li>
                    {{/opts}}
                </ul>
            </div>
            <div class="next_step">
                <input type="hidden" name="tarif_id" value="{{id}}">
                <input type="hidden" name="variant_id" value="{{v_id}}">
                <button class="next_step__button" onclick="nextStep(event)"></button>
            </div>
        </div>
    </article>
    {{/variants_data}}
</script>
<script id="tarif_confirm_template" type="text/html">
    <article class="item confirm">
        <div class="item__block">
            <h3>{{title}}</h3>
        </div>
        <div class="item__block">
            <div class="info">
                <p class="info__price">Период оплаты - {{pay_period}} месяцев</p>
                <p class="info__price">{{price_per_month}} ₽/мес</p>
                <ul class="info__options">
                    <li>разовый платеж - {{price}} ₽</li>
                    <li>со счета спишется - {{price}} ₽</li>
                </ul>
                <ul class="info__options_weak">
                    <li>вступит в силу - сегодня</li>
                    <li>активно до - {{date}}</li>
                </ul>
            </div>
        </div>
        <div class="item__block">
            <button class="submit">Выбрать</button>
        </div>
    </article>
</script>
</html>
