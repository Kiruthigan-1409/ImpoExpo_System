

document.addEventListener("DOMContentLoaded", () => {

    const Addbtn = document.getElementById("openbuyerform");
    const closebuyerbtn = document.getElementById("closebuyerform");
    const modal = document.getElementById("addBuyerModal");
    const form = document.getElementById("buyerform");
    const tableBody = document.querySelector(".data-table tbody");
    const productSelect = document.getElementById("productSelect");
    const activebuyers = document.querySelector(".activecountbuyer");
    const totalcitycount = document.querySelector(".citycount");
    const activeSuppliers = document.querySelector(".activesuppliercount");
    const countrycount = document.querySelector(".countrycount");
     const resetbtn = document.getElementById("resetbuyers");
     const openreportbtn = document.getElementById("buyerReport");  



    Addbtn.addEventListener("click", () => {

        form.reset();
        delete form.dataset.editId;
        document.getElementById("modalTitle").textContent = "Add Buyer";
        modal.style.display = "flex";
        $(form).parsley().reset();
    });
    closebuyerbtn.addEventListener("click", () => {
        modal.style.display = "none"
    });

    window.addEventListener("click", (e) => {
        if (e.target === modal) modal.style.display = "none";
    });

    openreportbtn.addEventListener("click", () => {
                window.location.href = "topSupplier.php";
        });

    //Load buyer products
    fetch("../backend/get_products.php")
        .then(res => res.json())
        .then(products => {
            productSelect.innerHTML = '<option value="">-- Select --</option>';
            products.forEach(p => {
                let option = document.createElement("option");
                option.value = p.product_id;
                option.textContent = p.product_name;
                productSelect.appendChild(option);
            });
            

        }).catch(err => console.error("Error loading products", err));

    //load buyer table
    function loadbuyerdata() {
        fetch("../backend/buyer.php")
            .then(res => res.json())
            .then(data => {
                tableBody.innerHTML = "";
                data.forEach((buyer, index) => {
                    const row = document.createElement("tr");
                    row.innerHTML =
                       `
                        <td>${buyer.buyername}</td>
                        <td>${buyer.b_address}</td>
                        <td>${buyer.b_city}</td>
                        <td>${buyer.b_email}</td>
                        <td data-contact="${buyer.b_contact}">+94 ${buyer.b_contact}</td>
                        <td data-productid="${buyer.product_id}">${buyer.product_name}</td>
                        <td><span class="${buyer.b_status.toLowerCase()}">${buyer.b_status}</span></td>
                        <td class="actions">
                        <button class="action-btn edit" data-id="${buyer.buyer_id}" title="Edit">
                            <i class="fa-regular fa-pen-to-square fa-lg"></i>
                        </button>
                        <button class="action-btn delete" data-id="${buyer.buyer_id}" title="Delete">
                            <i class="fa-regular fa-trash-can fa-lg" style="color: #ff0000;"></i>
                        </button>
                        </td>

                        `;
                    tableBody.appendChild(row);
                });

            }).catch(err => console.error("Error loading buyers", err));

    }
    loadbuyerdata();

     resetbtn.addEventListener("click", () => {
            loadbuyerdata();
            setTimeout(() => {
                tableBody.style.opacity = "1";
            }, 300)
            });

    //buyer add and edit form
    form.addEventListener("submit", function (e) {
        e.preventDefault();

        if ($(form).parsley().isValid()) {
            const formData = new FormData(this);

            let url;
            let action;
            if (form.dataset.editId) {

                url = `../backend/updatebuyer.php?id=${form.dataset.editId}`;
                action = "Updated";
            } else {

                url = "../backend/Addbuyer.php";
                action = "Added";
            }

            fetch(url, {
                method: "POST",
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {

                        Swal.fire({
                            title: `${action}!`,
                            text: data.message,
                            icon: 'success',
                            color: '#1b2d0bff',
                            background: "#fffffff4 url(pic/success.jpg) no-repeat center/cover",
                            confirmButtonColor: '#144724ff',
                            timer: 1000,
                            timerProgressBar: true,
                        });

                        modal.style.display = "none";
                        form.reset();
                        delete form.dataset.editId;
                        loadbuyerdata();
                        activebuyercount();
                        citycount();
                        activesuppliercount();
                        importcountrycount();

                    } else {

                        Swal.fire({
                            title: 'Error!',
                            text: data.message,
                            icon: 'error',
                            confirmButtonColor: '#be5646ff',
                        });
                    }
                })
            .catch(err => console.error("Error saving buyer:", err));
        }
    });


    //edit btn
    tableBody.addEventListener("click", (e) => {
        if (e.target.closest(".edit")) {
            const btn = e.target.closest(".edit");
            
            const id = btn.dataset.id;

            const row = btn.closest("tr");
            form.buyername.value = row.children[0].textContent;
            form.b_address.value = row.children[1].textContent;
            form.b_city.value = row.children[2].textContent;
            form.b_email.value = row.children[3].textContent;
            form.b_contact.value = row.children[4].dataset.contact;
            const productId = row.children[5].dataset.productid;
            form.b_productid.value = productId;
            form.b_status.value = row.children[6].textContent;

            form.dataset.editId = id;
            document.getElementById("modalTitle").textContent = "Edit buyer";
            $(form).parsley().reset();
            modal.style.display = "flex";
        }
    });

    //delete btn

    let deleteId = null;
    tableBody.addEventListener("click", (e) => {
        if (e.target.closest(".delete")) {
            const id = e.target.closest(".delete").dataset.id;

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this buyer record!",
                icon: 'warning',
                width: 500,
                padding: "3em",
                color: "#2e2c42ff",
                background: "#ffffffea url(pic/red.jpg) no-repeat center/cover",
                showCancelButton: true,
                confirmButtonColor: '#631e1eff',
                cancelButtonColor: '#0c6532ff',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {

                    fetch(`../backend/deletebuyer.php?id=${id}`, { method: "GET" })
                        .then(res => res.json())
                        .then(data => {
                            Swal.fire({
                                title: "Buyer deleted successfully!",
                                icon: "success",
                                color: "#0d380fff",
                                background: "#ffffffd7 url(pic/success.jpg) no-repeat center/cover",
                                confirmButtonColor: '#173f3cff',

                            });
                            loadbuyerdata();
                            activebuyercount();
                            citycount();
                            importcountrycount();


                        })
                        .catch(err => console.error(err));
                }
            });
        }
    });



    //active buyer count
    function activebuyercount() {

        fetch("../backend/s_count.php")
            .then(res => res.json())
            .then(data => {
                activebuyers.textContent = data.activebuyer;
            })

            .catch(err => console.error("Error fetching active buyers count:", err));
    }
    activebuyercount();

    //active city count
    function citycount() {
        fetch("../backend/s_count.php")
            .then(res => res.json())
            .then(data => {
                totalcitycount.textContent = data.cities;
            })

            .catch(err => console.error("Error fetching city count:", err));
    }


    citycount();

    //active supplier count
    function activesuppliercount() {
        fetch("../backend/s_count.php")
            .then(res => res.json())
            .then(data => {
                activeSuppliers.textContent = data.activesupplier;
            })

            .catch(err => console.error("Error fetching active suppliers count:", err));
    }

    activesuppliercount();

    //country count
    function importcountrycount() {
        fetch("../backend/s_count.php")
            .then(res => res.json())
            .then(data => {
                countrycount.textContent = data.countries;
            })

            .catch(err => console.error("Error fetching import country count:", err));
    }

    importcountrycount();



    //buyer search

     document.getElementById("buyersearch").addEventListener("keyup", function() {
                const query = this.value.toLowerCase();
                const rows = document.querySelectorAll("#buyerTable tbody tr");

                let matchFound = false;

                rows.forEach(row => {
                        const name    = row.cells[0]?.textContent.toLowerCase() || "";
                        const email   = row.cells[3]?.textContent.toLowerCase() || "";
                        const contact = row.cells[4]?.textContent.toLowerCase() || "";

                        const match = name.includes(query) || email.includes(query) || contact.includes(query);

                        if (match) {
                                row.style.display = "";
                                matchFound = true;
                            } else {
                                row.style.display = "none";
                            }

            });

            let noResultRow = document.querySelector("#no-result-row");
            if (noResultRow) noResultRow.remove();

            // no result found
            if (!matchFound) {
                const tbody = document.querySelector("#buyerTable tbody");
                const tr = document.createElement("tr");
                tr.id = "no-result-row";

                const td = document.createElement("td");
                td.setAttribute("colspan", rows[0]?.cells.length || 9);
                td.textContent = "No results found";
                    td.style.textAlign = "center";
                    td.style.fontWeight = "bold";
                    td.style.color = "red";
                    td.style.background= "#fbc4b9ff";
                    td.style.borderRadius="10px";
                    td.style.padding = "10px";

                tr.appendChild(td);
                tbody.appendChild(tr);
            }
        });

        //buyer filter
        function bindDropdownOptions() {
                document.querySelectorAll(".custom-dropdown").forEach(dropdown => {
                        const selected = dropdown.querySelector(".dropdown-selected");
                        const options = dropdown.querySelector(".dropdown-options");

                        options.querySelectorAll("li").forEach(option => {
                            option.addEventListener("click", () => {
                                selected.textContent = option.textContent;
                                options.style.display = "none";
                                applyFilters(); 
                            });
                        });
                    });
                }

          //filter
            document.querySelectorAll(".custom-dropdown").forEach(dropdown => {
                const selected = dropdown.querySelector(".dropdown-selected");
                const options = dropdown.querySelector(".dropdown-options");
                let hideTimeout;

                
                selected.addEventListener("click", () => {
                    options.style.display = options.style.display === "block" ? "none" : "block";
                });

                
                options.querySelectorAll("li").forEach(option => {
                    option.addEventListener("click", () => {
                    selected.textContent = option.textContent;
                
                    options.style.display = "none";
                    });
                });

                dropdown.addEventListener("mouseleave", () => {
                    hideTimeout = setTimeout(() => {
                    options.style.display = "none";
                    }, 200); 
                });

                dropdown.addEventListener("mouseenter", () => {
                    clearTimeout(hideTimeout);
                });
            });

                
    // Fetch filters 
    fetch("../backend/getFilters.php")
            .then(response => response.json())
            .then(data => {
                
                const cityList = document.querySelector(".filter-city .dropdown-options");
                data.cities.forEach(city => {
                    const li = document.createElement("li");
                    li.textContent = city;
                    cityList.appendChild(li);
                });

               
                const productList = document.querySelector(".filter-product .dropdown-options");
                data.buyerproducts.forEach(product => {
                    const li = document.createElement("li");
                    li.textContent = product;
                    productList.appendChild(li);
                });

                
                bindDropdownOptions();
            })
            .catch(err => console.error("Error fetching filters:", err));


    //apply filter function
     function applyFilters() {
        const selectedCity = document.querySelector(".filter-city .dropdown-selected").textContent;
        const selectedStatus  = document.querySelector(".filter-status .dropdown-selected").textContent;
        const selectedProduct = document.querySelector(".filter-product .dropdown-selected").textContent;

        const rows = document.querySelectorAll("#buyerTable tbody tr");

        rows.forEach(row => {
            const city = row.cells[2]?.textContent.trim() || "";
            const product = row.cells[5]?.textContent.trim() || "";
            const status  = row.cells[6]?.textContent.trim() || "";

            let show = true;
            if (selectedCity !== "All" && city !== selectedCity) show = false;
            if (selectedStatus !== "All" && status !== selectedStatus) show = false;
            if (selectedProduct !== "All" && product !== selectedProduct) show = false;

            row.style.display = show? "" : "none";
        });
    }

    let mapInitialized = false; 
    let map;
    document.getElementById("viewMapBtn").addEventListener("click", () => {
            const container = document.getElementById("mapContainer");

            // Toggle visibility
            if (container.style.display === "none") {
                container.style.display = "block";

                // Initialize map  once
                if (!mapInitialized) {
                    map = L.map("networkMap").setView([20, 0], 2);
                    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(map);

                    fetch("../backend/getNetworkData.php")
                        .then(res => res.json())
                        .then(data => {
                            data.suppliers.forEach(supplier => {
                                L.marker([supplier.lat, supplier.lng])
                                    .addTo(map)
                                    .bindPopup(`<b>Supplier:</b> ${supplier.suppliername}<br>${supplier.s_country}`);
                            });

                            data.buyers.forEach(buyer => {
                                L.marker([buyer.lat, buyer.lng], {
                                    icon: L.icon({
                                        iconUrl: "https://cdn-icons-png.flaticon.com/512/2991/2991231.png",
                                        iconSize: [25, 25],
                                    }),
                                })
                                    .addTo(map)
                                    .bindPopup(`<b>Buyer:</b> ${buyer.buyername}<br>${buyer.b_city}`);
                            });

                            data.connections.forEach(conn => {
                                L.polyline([conn.supplierCoords, conn.buyerCoords], {
                                    color: "#008080",
                                    weight: 2,
                                    opacity: 0.6,
                                }).addTo(map);
                            });
                        })
                        .catch(err => console.error("Error loading network data:", err));

                    mapInitialized = true;
                }

            } else {
                container.style.display = "none";
            }
        });


             

       






});

















