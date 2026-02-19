let saleItems = [];
let allBooks = []; 

// Load Customers from Database
async function loadCustomers() {
    try {
        const res = await fetch("../api/customers.php");
        const data = await res.json();
        const custSel = document.getElementById("customer");
        if (custSel) {
            custSel.innerHTML = data.map(c => `<option value="${c.id}">${c.name}</option>`).join("");
        }
    } catch (err) {
        console.error("Error loading customers:", err);
    }
}

// Load Books and handle Image paths
async function loadBooks() {
    const qEl = document.getElementById("q");
    const q = qEl ? qEl.value.trim() : "";
    
    try {
        const res = await fetch(`../api/books.php?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        allBooks = data; 

        const tbody = document.getElementById("books");
        tbody.innerHTML = data.map(b => {
            // Fix path: remove /system/ and go up one level
            const imgPath = b.image ? b.image.replace('/system/', '../') : 'https://via.placeholder.com/40';
            return `
            <tr>
                <td><img src="${imgPath}" width="40" class="rounded"></td>
                <td><strong>${b.title}</strong><br><small class="text-muted">ID: ${b.id}</small></td>
                <td class="text-primary fw-bold">$${parseFloat(b.price).toFixed(2)}</td>
                <td><span class="badge ${b.stock > 5 ? 'bg-success' : 'bg-danger'}">${b.stock}</span></td>
            </tr>`;
        }).join("");

        const bookSel = document.getElementById("book");
        bookSel.innerHTML = data.map(b => 
            `<option value="${b.id}">${b.title} ($${b.price})</option>`
        ).join("");
    } catch (err) {
        console.error("Error loading books:", err);
    }
}

// Add Item to Invoice with Stock Check
function addItem() {
    const bookId = parseInt(document.getElementById("book").value);
    const qty = parseInt(document.getElementById("qty").value);
    const book = allBooks.find(b => b.id == bookId);
    
    if (!book) return;
    if (qty > book.stock) return alert("Not enough stock!"); //

    const existing = saleItems.find(item => item.book_id === bookId);
    if (existing) {
        existing.qty += qty;
    } else {
        saleItems.push({
            book_id: bookId,
            title: book.title,
            price: parseFloat(book.price),
            qty: qty
        });
    }
    renderItems();
}

// Render the Invoice List and Calculate Total
function renderItems() {
    const itemsList = document.getElementById("items");
    const totalDisplay = document.getElementById("grand_total");
    
    if (saleItems.length === 0) {
        itemsList.innerHTML = `<li class="list-group-item text-center py-4 text-muted small">No items added yet.</li>`;
        totalDisplay.innerText = "$0.00";
        return;
    }

    let grandTotal = 0;
    itemsList.innerHTML = saleItems.map((it, i) => {
        const lineTotal = it.price * it.qty;
        grandTotal += lineTotal;
        return `
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-bold">${it.title}</div>
                <small class="text-muted">$${it.price.toFixed(2)} x ${it.qty}</small>
            </div>
            <div class="text-end">
                <div class="fw-bold text-dark">$${lineTotal.toFixed(2)}</div>
                <button class="btn btn-sm text-danger p-0" onclick="removeItem(${i})">Remove</button>
            </div>
        </li>`;
    }).join("");

    totalDisplay.innerText = `$${grandTotal.toFixed(2)}`;
}

// ក្នុង app.js ប្រាកដថាមានកូដនេះ
async function addBook() {
    const titleEl = document.getElementById("b_title");
    const priceEl = document.getElementById("b_price");
    const stockEl = document.getElementById("b_stock");
    
    // ចាប់យកតម្លៃ
    const payload = { 
        title: titleEl.value, 
        price: priceEl.value, 
        stock: stockEl.value 
    };

    try {
        const res = await fetch("../api/books.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.status === "success") {
            alert("Book added!");
            loadBooks(); // ហៅមកវិញដើម្បីបង្ហាញសៀវភៅថ្មី
        }
    } catch (err) {
        console.error("Error:", err);
    }
}

function removeItem(index) {
    saleItems.splice(index, 1);
    renderItems();
}

function clearItemsUI() {
    saleItems = [];
    renderItems();
}

// Save Sale to Database
async function createSale() {
    if (saleItems.length === 0) return alert("Please add items first.");

    const customer_id = parseInt(document.getElementById("customer").value);
    const saveBtn = document.getElementById("saveInvoiceBtn");
    
    saveBtn.disabled = true;
    saveBtn.innerHTML = "Saving...";

    try {
        const res = await fetch("../api/sales.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ customer_id, items: saleItems })
        });
        
        const data = await res.json();
        if (data.status === "success") {
            alert("Success! Invoice #" + data.invoice_no + " created.");
            clearItemsUI();
            loadBooks(); 
        } else {
            alert("Error: " + data.message);
        }
    } catch (error) {
        alert("Failed to connect to server. Ensure you use XAMPP/Localhost, not Port 5500.");
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = "Confirm & Save Invoice";
    }
}

// Init
loadCustomers();
loadBooks();