/* style.css */

/* Reset sedikit */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f9;
    margin: 20px;
    color: #333;
}

/* Judul */
h2, h3 {
    color: #2c3e50;
}

/* Link Logout */
a {
    text-decoration: none;
    color: #e74c3c;
    font-weight: bold;
}

a:hover {
    text-decoration: underline;
}

/* Tabel */
table {
    border-collapse: collapse;
    width: 100%;
    margin-bottom: 20px;
    background-color: #fff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

table th, table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
}

table th {
    background-color: #3498db;
    color: white;
}

/* Baris ganjil */
table tr:nth-child(odd) {
    background-color: #f9f9f9;
}

/* Form */
form input[type="text"], form select {
    padding: 5px;
    margin-right: 5px;
    border-radius: 3px;
    border: 1px solid #ccc;
}

form button {
    padding: 5px 10px;
    background-color: #2ecc71;
    color: white;
    border: none;
    border-radius: 3px;
    cursor: pointer;
}

form button:hover {
    background-color: #27ae60;
}

/* Button Reject */
form button[name="reject"] {
    background-color: #e74c3c;
}

form button[name="reject"]:hover {
    background-color: #c0392b;
}

/* Foto */
td img {
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

