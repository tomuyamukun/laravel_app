window.onload = function () {
    let steps = document.getElementById('steps');
    Sortable.create(steps, {
        animation: 150,
        handle: '.handle',
        onEnd: function (evt) {
            let items = steps.querySelectorAll('.step')
            items.forEach(function (item, index) {
                item.querySelector('.step-number').innerHTML = '手順' + (index + 1);
            })
        }
    })
}
