let saleItems = [];

async function loadBooks() {
  const q = document.getElementById("q").value.trim();
  const res = await fetch(`../api/books.php?q=${encodeURIComponent(q)}`);
  const data = await res.json();

  const tbody = document.getElementById("books");
  tbody.innerHTML = data.map(b =>
    `<tr>
      <td>${b.id}</td>
      <td>${b.title}</td>
      <td>${b.price}</td>
      <td>${b.stock}</td>
    </tr>`
  ).join("");

  // fill book select
  const bookSel = document.getElementById("book");
  bookSel.innerHTML = data.map(b => `<option value="${b.id}">${b.title} ($${b.price}) [${b.stock}]</option>`).join("");
}

async function addBook() {
  const payload = {
    title: document.getElementById("b_title").value,
    price: document.getElementById("b_price").value,
    stock: document.getElementById("b_stock").value
  };
  await fetch("../api/books.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload)
  });
  loadBooks();
}

async function loadCustomers() {
  const res = await fetch("../api/customers.php");
  const data = await res.json();
  document.getElementById("customer").innerHTML =
    data.map(c => `<option value="${c.id}">${c.name}</option>`).join("");
}

function addItem() {
  const book_id = parseInt(document.getElementById("book").value, 10);
  const qty = parseInt(document.getElementById("qty").value, 10);
  saleItems.push({ book_id, qty });
  renderItems();
}

function renderItems() {
  document.getElementById("items").innerHTML =
    saleItems.map((it, i) => `<li>#${i+1} book_id=${it.book_id} qty=${it.qty}</li>`).join("");
}

async function createSale() {
  const customer_id = parseInt(document.getElementById("customer").value, 10);
  const res = await fetch("../api/sales.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ customer_id, items: saleItems })
  });
  const data = await res.json();
  alert(JSON.stringify(data));
  saleItems = [];
  renderItems();
  loadBooks(); // refresh stock
}

loadCustomers();
loadBooks();