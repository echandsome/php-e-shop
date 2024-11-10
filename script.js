const productsForm = document.querySelector('.products-form');
const searchIcon = document.querySelector('.search-toggle');
const searchInput = document.querySelector('.search input');
if (searchIcon && searchInput) {
    searchIcon.onclick = () => {
        searchIcon.style.display = 'none';
        searchIcon.parentElement.querySelector('input').style.display = 'block';
        searchIcon.parentElement.querySelector('input').focus();
    };
    searchInput.onkeyup = event => {
        if (event.keyCode === 13 && searchInput.value.length > 0) {
            window.location.href = encodeURI(searchInput.dataset.url + searchInput.value);
        }
    };
}
if (document.querySelector('.product-img-small')) {
    let imgs = document.querySelectorAll('.product-img-small img');
    imgs.forEach(img => {
        img.onmouseover = () => {
            document.querySelector('.product-img-large img').src = img.src;
            imgs.forEach(i => i.parentElement.classList.remove('selected'));
            img.parentElement.classList.add('selected');
        };
        img.onclick = () => {
            document.body.insertAdjacentHTML('beforeend', `
            <div class="img-modal">
                <div>
                    <a href="#" class="close">&times;</a>
                    <img src="${img.src}" alt="">
                </div>
            </div>
            `);
            document.querySelector('.img-modal div').style.height = (document.querySelector('.img-modal img').height+80) + 'px';
            document.querySelector('.img-modal .close').onclick = event => {
                event.preventDefault();
                document.querySelector('.img-modal').remove();
            };
            document.querySelector('.img-modal').onclick = event => {
                if (event.target.classList.contains('img-modal')) document.querySelector('.img-modal').remove();
            };
        };
    });
}
if (document.querySelector('.product .product-form')) {
    let updatePrice = () => {
        let price = parseFloat(document.querySelector('.product .price').dataset.price);
        document.querySelectorAll('.product .product-form .option').forEach(e => {
            if (e.value) {
                let optionPrice = e.classList.contains('text') || e.classList.contains('datetime') ? e.dataset.price : 0.00;
                optionPrice = e.classList.contains('select') ? e.options[e.selectedIndex].dataset.price : optionPrice;
                optionPrice = (e.classList.contains('radio') || e.classList.contains('checkbox')) && e.checked ? e.dataset.price : optionPrice;
                price = (e.classList.contains('select') ? e.options[e.selectedIndex].dataset.modifier : e.dataset.modifier) == 'add' ? price+parseFloat(optionPrice) : price-parseFloat(optionPrice);
            }
        });
        document.querySelector('.product .price').innerHTML = currency_code + (price > 0.00 ? price.toFixed(2) : 0.00);
    };
    let updateQty = () => {
        if (!document.querySelector('.product .product-form #quantity')) return;
        let qtyEle = document.querySelector('.product .product-form #quantity');
        let qty = parseInt(qtyEle.dataset.quantity);
        document.querySelectorAll('.product .product-form .option').forEach(e => {
            if (e.value) {
                let optionQty = e.classList.contains('text') || e.classList.contains('datetime') ? e.dataset.quantity : 0;
                optionQty = e.classList.contains('select') ? e.options[e.selectedIndex].dataset.quantity : optionQty;
                optionQty = (e.classList.contains('radio') || e.classList.contains('checkbox')) && e.checked ? e.dataset.quantity : optionQty;
                if ((qty > parseInt(optionQty) || qty == -1) && parseInt(optionQty) > 0) {
                    qty = parseInt(optionQty);
                }
            }
        });
        if (qty == -1) {
            qtyEle.removeAttribute('max');
        } else {
            qtyEle.max = qty;
        }
    };
    document.querySelectorAll('.product .product-form .option').forEach(ele => ele.onchange = () => {
        updatePrice();
        updateQty();
        let imgs = document.querySelectorAll('.product-img-small img');
        imgs.forEach(img => {
            if (img.alt.includes(ele.name.toLowerCase() + '-' + ele.value.toLowerCase())) {
                document.querySelector('.product-img-large img').src = img.src;
                imgs.forEach(i => i.parentElement.classList.remove('selected'));
                img.parentElement.classList.add('selected');
            }
        });
    });
    updatePrice();
    updateQty();
}
if (productsForm) {
    document.querySelector('.sortby select').onchange = () => productsForm.submit();
    document.querySelectorAll('.products-filters .filter-title').forEach(ele => {
        ele.onclick = () => ele.closest('.products-filter').classList.toggle('closed');
    });
    document.querySelectorAll('.products-filters .show-more').forEach(ele => {
        ele.onclick = event => {
            event.preventDefault();
            ele.closest('.filter-options').querySelectorAll('label').forEach(label => label.classList.remove('hidden'));
            ele.remove();
        };
    });
    document.querySelectorAll('.products-filters input').forEach(ele => {
        ele.onchange = () => productsForm.submit();
    });
}
if (document.querySelector('.responsive-toggle')) {
    document.querySelector('.responsive-toggle').onclick = event => {
        event.preventDefault();
        let nav_display = document.querySelector('header nav').style.display;
        document.querySelector('header nav').style.display = nav_display == 'block' ? 'none' : 'block';
    };
}
if (document.querySelector('.cart .ajax-update')) {
    document.querySelectorAll('.cart .ajax-update').forEach(ele => ele.onchange = () => {
        let formEle = document.querySelector('.cart form');
        let formData = new FormData(formEle);
        formData.append('update', 'Update');
        fetch(formEle.action, {
            method: 'POST',
            body: formData
        }).then(response => response.text()).then(html => {
            let doc = (new DOMParser()).parseFromString(html, 'text/html');
            document.querySelector('.total').innerHTML = doc.querySelector('.total').innerHTML;
            document.querySelectorAll('.product-total').forEach((e,i) => {
                e.innerHTML = doc.querySelectorAll('.product-total')[i].innerHTML;
            });
        });
    });
}
const checkoutHandler = () => {
    document.querySelectorAll('.checkout .ajax-update').forEach(ele => {
        ele.onchange = () => {
            let formEle = document.querySelector('.checkout form');
            let formData = new FormData(formEle);
            formData.append('update', 'Update');
            fetch(formEle.action, {
                method: 'POST',
                body: formData
            }).then(response => response.text()).then(html => {
                let doc = (new DOMParser()).parseFromString(html, 'text/html');
                document.querySelector('.summary').innerHTML = doc.querySelector('.summary').innerHTML;
                document.querySelector('.total').innerHTML = doc.querySelector('.total').innerHTML;
                document.querySelector('.discount-code .result').innerHTML = doc.querySelector('.discount-code .result').innerHTML;
                document.querySelector('.shipping-methods-container').innerHTML = doc.querySelector('.shipping-methods-container').innerHTML;
                checkoutHandler();
            });
        };
        if (ele.name == 'discount_code') {
            ele.onkeydown = event => {
                if (event.key == 'Enter') {
                    event.preventDefault();
                    ele.onchange();
                }
            };
        }
    });
};
checkoutHandler();