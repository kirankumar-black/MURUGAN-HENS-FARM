// Animal Mart - Local Cart Management

document.addEventListener("DOMContentLoaded", () => {
    updateCartBadge();
});

function getCart() {
    return JSON.parse(localStorage.getItem("cart") || "[]");
}

function saveCart(cart) {
    localStorage.setItem("cart", JSON.stringify(cart));
    updateCartBadge();
}

function addToCart(animalId) {
    let cart = getCart();
    animalId = parseInt(animalId);
    
    if (cart.includes(animalId)) {
        showToast("Animal is already in your cart.", "error");
        return;
    }
    
    cart.push(animalId);
    saveCart(cart);
    showToast("Animal added to cart successfully!", "success");
}

function removeFromCart(animalId) {
    let cart = getCart();
    animalId = parseInt(animalId);
    
    cart = cart.filter(id => id !== animalId);
    saveCart(cart);
    showToast("Animal removed from cart.", "success");
    
    // Dispatch custom event to reload cart page if active
    window.dispatchEvent(new CustomEvent('cartUpdated'));
}

function getCartCount() {
    return getCart().length;
}

function clearCart() {
    localStorage.removeItem("cart");
    updateCartBadge();
}

function updateCartBadge() {
    const badge = document.getElementById("cart-badge");
    if (!badge) return;
    
    const count = getCartCount();
    badge.innerText = count;
    
    if (count > 0) {
        badge.classList.remove("d-none");
    } else {
        badge.classList.add("d-none");
    }
}
