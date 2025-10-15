


document.addEventListener("DOMContentLoaded", () => {
        const openBtn = document.getElementById("openAddForm");
        const closeBtn = document.getElementById("closeForm");
        const modal = document.getElementById("addSupplierModal");
        const form = document.getElementById("supplierForm");
        const tableBody = document.querySelector(".data-table tbody");
        const productSelect = document.getElementById("productSelect");
        const input = document.getElementById("countryInput");
        const list = document.getElementById("countryList");
        const countryCodeInput = document.querySelector("input[name='s_country_code']"); 
        const activebuyers = document.querySelector(".activecountbuyer");
        const totalcitycount = document.querySelector(".citycount");
        const activeSuppliers = document.querySelector(".activesuppliercount");
        const countrycount = document.querySelector(".countrycount");
        const openreportbtn = document.getElementById("openReportModal");   
        const reportModal = document.querySelector("#monthlyReportModal");
        const closeReportBtn = document.getElementById("closeReportModal");
        const resetbtn = document.getElementById("resetSuppliers")


        openBtn.addEventListener("click", () => {
                form.reset();
                delete form.dataset.editId;
                document.getElementById("modalTitle").textContent = "Add Supplier";
                modal.style.display = "flex";
                $(form).parsley().reset();
            });
        
        closeBtn.addEventListener("click", () => modal.style.display = "none");
        
        window.addEventListener("click", e => {
            if(e.target === modal) modal.style.display = "none";
            if(e.target === reportModal) reportModal.style.display = "none";
        });


        openreportbtn.addEventListener("click", () => {
                window.location.href = "topSupplier.php";
        });

        

        //  Load Products
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
            })
        .catch(err => console.error("Error loading products:", err));

        //load suppliers
        function loadSuppliers() {
            fetch("../backend/supplier.php")
                .then(res => res.json())
                .then(data => {
                    tableBody.innerHTML = "";
                    data.forEach((supplier,index) => {
                        const row = document.createElement("tr");
                        row.innerHTML = `
                            <td>${supplier.suppliername}</td>
                            <td>${supplier.s_company}</td>
                            <td>${supplier.s_country}</td>
                            <td>${supplier.s_city}</td>
                            <td>${supplier.s_email}</td>
                            <td>${supplier.contact}</td>
                            <td data-productid="${supplier.product_id}">${supplier.product_name}</td>
                            <td><span class="${supplier.s_status.toLowerCase()}"> ${supplier.s_status}</span></td>
                             <td class="actions">
                                <button class="action-btn edit" data-id="${supplier.supplier_id}" title="Edit">
                                <i class="fa-regular fa-pen-to-square fa-lg"></i>
                            </button>
                            <button class="action-btn delete" data-id="${supplier.supplier_id}" title="Delete">
                                <i class="fa-regular fa-trash-can fa-lg" style="color: #ff0000;"></i>
                            </button>
                            </td>
                        `;
                        tableBody.appendChild(row);
                    });
                })
                .catch(err => console.error("Error loading suppliers:", err));
        }

        loadSuppliers(); 


        //reset button
        resetbtn.addEventListener("click", () => {
            loadSuppliers();
            setTimeout(() => {
                tableBody.style.opacity = "1";
            }, 300)
            });


        
        form.addEventListener("submit", function(e){     
            
        e.preventDefault();
        if ($(form).parsley().isValid()) {

            const formData = new FormData(this);

            let url;
            let action; 
            if (form.dataset.editId) {
                
                url = `../backend/updatesupplier.php?id=${form.dataset.editId}`;
                action = "Updated";
            } else {
               
                url = "../backend/Addsupplier.php";
                action = "Added";
            }

            fetch(url, {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                   
                    Swal.fire({
                        title: `${action}!`,
                        text: data.message,
                        icon: 'success',
                        color:'#1b2d0bff',
                        background: "#fffffff4 url(pic/success.jpg) no-repeat center/cover",
                        confirmButtonColor: '#144724ff',
                        timer: 1000,
                        timerProgressBar: true,
                    });

                    modal.style.display = "none";
                    form.reset();
                    delete form.dataset.editId; 
                    loadSuppliers(); 
                    activesuppliercount();
                    importcountrycount();
                    activebuyercount();
                    citycount();
                } else {
               
                    Swal.fire({
                        title: 'Error!',
                        text: data.message,
                        icon: 'error',
                        confirmButtonColor: '#be5646ff',
                    });
                }
            })
           
    .catch(err => console.error("Error saving supplier:", err));
        }
    });




    let countries = [];

        
        // Fetch countries 
        fetch("../backend/countries.php")
            .then(res => res.json())
            .then(data => {
                countries = data; 
            })
            .catch(err => console.error("Error fetching countries:", err));

       
        input.addEventListener("input", () => {
            const value = input.value.toLowerCase();
            list.innerHTML = "";

            if (!value) {
                list.style.display = "none";
                return;
            }

            const filtered = countries.filter(c => c.c_name.toLowerCase().includes(value));

            filtered.forEach(c => {
                const li = document.createElement("li");
                li.textContent = c.c_name;
                li.addEventListener("click", () => {
                    input.value = c.c_name;
                    countryCodeInput.value = "+" + c.phone_code;
                    list.style.display = "none";
                });
                list.appendChild(li);
            });

            list.style.display = filtered.length ? "block" : "none";
        });

        
        document.addEventListener("click", e => {
            if (!input.contains(e.target) && !list.contains(e.target)) {
                list.style.display = "none";
            }
        });


        //edit btn
       tableBody.addEventListener("click", (e) => {
            if (e.target.closest(".edit")) {
                const btn = e.target.closest(".edit");
           
                const id = btn.dataset.id;

                const row = btn.closest("tr");
                form.suppliername.value = row.children[0].textContent;
                form.s_company.value = row.children[1].textContent;
                form.s_country.value = row.children[2].textContent;
                form.s_city.value = row.children[3].textContent;
                form.s_email.value = row.children[4].textContent;

                const contact = row.children[5].textContent.split(" ");
                form.s_country_code.value = contact[0] || "";
                form.s_contact.value = contact[1] || "";

                const productId = row.children[6].dataset.productid;
                form.s_productid.value = productId;
                form.s_status.value = row.children[7].textContent;

                form.s_status.value = row.children[7].textContent.trim();
                
                form.dataset.editId = id;
                document.getElementById("modalTitle").textContent = "Edit Supplier";
                $(form).parsley().reset();
                modal.style.display = "flex";
            }
        });

        //delete
       let deleteId = null; 
       tableBody.addEventListener("click", (e) => {
            if (e.target.closest(".delete")) {
                const id = e.target.closest(".delete").dataset.id;

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this record!",
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
                        
                        fetch(`../backend/deletesupplier.php?id=${id}`, { method: "GET" })
                        .then(res => res.json())
                        .then(data => {                   
                                Swal.fire({
                                title: "Supplier deleted successfully!",
                                icon: "success",
                                color: "#0d380fff",
                                background: "#ffffffd7 url(pic/success.jpg) no-repeat center/cover",
                                confirmButtonColor: '#173f3cff',
                              
                            });
                            loadSuppliers(); 
                            activesuppliercount();
                            importcountrycount();
                            activebuyercount();
                            citycount();
                        })
                        .catch(err => console.error(err));
                    }
                });
            }
        });

        //supplier count
        function activesuppliercount(){
        fetch("../backend/s_count.php")
        .then(res => res.json())
        .then(data => {
            activeSuppliers.textContent = data.activesupplier; 
        })
    
        .catch(err => console.error("Error fetching active suppliers count:", err));}
        activesuppliercount();

        //country count
        function importcountrycount(){
        fetch("../backend/s_count.php")
        .then(res => res.json())
        .then(data => {
            countrycount.textContent = data.countries; 
        })
    
        .catch(err => console.error("Error fetching import country count:", err));}
        importcountrycount();

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


        // Supplier search
            document.getElementById("suppliersearch").addEventListener("keyup", function() {
                const query = this.value.toLowerCase();
                const rows = document.querySelectorAll("#supplierTable tbody tr");

                let matchFound = false;

                rows.forEach(row => {
                        const name    = row.cells[0]?.textContent.toLowerCase() || "";
                        const email   = row.cells[4]?.textContent.toLowerCase() || "";
                        const contact = row.cells[5]?.textContent.toLowerCase() || "";

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
                const tbody = document.querySelector("#supplierTable tbody");
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
                
                const countryList = document.querySelector(".filter-country .dropdown-options");
                data.countries.forEach(country => {
                    const li = document.createElement("li");
                    li.textContent = country;
                    countryList.appendChild(li);
                });

               
                const productList = document.querySelector(".filter-product .dropdown-options");
                data.products.forEach(product => {
                    const li = document.createElement("li");
                    li.textContent = product;
                    productList.appendChild(li);
                });

                
                bindDropdownOptions();
            })
            .catch(err => console.error("Error fetching filters:", err));


    //apply filter function
     function applyFilters() {
        const selectedCountry = document.querySelector(".filter-country .dropdown-selected").textContent;
        const selectedStatus  = document.querySelector(".filter-status .dropdown-selected").textContent;
        const selectedProduct = document.querySelector(".filter-product .dropdown-selected").textContent;

        const rows = document.querySelectorAll("#supplierTable tbody tr");

        rows.forEach(row => {
            const country = row.cells[2]?.textContent.trim() || "";
            const product = row.cells[6]?.textContent.trim() || "";
            const status  = row.cells[7]?.textContent.trim() || "";

            let show = true;
            if (selectedCountry !== "All" && country !== selectedCountry) show = false;
            if (selectedStatus !== "All" && status !== selectedStatus) show = false;
            if (selectedProduct !== "All" && product !== selectedProduct) show = false;

            row.style.display = show ? "" : "none";
        });
    }

             


    });

    

  


