
                    <script>
                        document.addEventListener("DOMContentLoaded", () => {
                            let isDown = false;
                            let startX;
                            let scrollLeft;
                            let slider;

                            document.addEventListener("mousedown", (e) => {
                                slider = e.target.closest(".fi-ta-content");
                                if (!slider) return;
                                
                                // Ignore interactive elements
                                const isInteractive = e.target.closest("button, a, input, select, textarea, [role='button'], label");
                                if (isInteractive) {
                                    slider = null;
                                    return;
                                }

                                isDown = true;
                                slider.style.cursor = "grabbing";
                                startX = e.pageX - slider.offsetLeft;
                                scrollLeft = slider.scrollLeft;
                            });

                            document.addEventListener("mouseup", () => {
                                isDown = false;
                                if(slider) slider.style.cursor = "";
                            });

                            document.addEventListener("mousemove", (e) => {
                                if (!isDown || !slider) return;
                                
                                e.preventDefault(); // Mencegah highlight teks saat drag
                                const x = e.pageX - slider.offsetLeft;
                                const walk = (x - startX) * 1.5; // Kecepatan scroll
                                slider.scrollLeft = scrollLeft - walk;
                            });

                            // --- Top Scrollbar Logic ---
                            function initTopScrollbars() {
                                document.querySelectorAll(".fi-ta-content").forEach(container => {
                                    // Pastikan kita belum menambahkan scrollbar ke container ini
                                    if (container.parentElement.querySelector(".custom-top-scrollbar")) return;

                                    // Buat wrapper scrollbar di atas
                                    const topScroll = document.createElement("div");
                                    topScroll.className = "custom-top-scrollbar";
                                    topScroll.style.overflowX = "auto";
                                    topScroll.style.overflowY = "hidden";
                                    // Supaya style menyatu dengan desain admin
                                    topScroll.style.marginBottom = "4px";
                                    topScroll.style.borderRadius = "8px";
                                    
                                    // Sembunyikan scrollbar bawaan di bawah dengan CSS
                                    container.style.scrollbarWidth = "none"; // Firefox
                                    container.style.msOverflowStyle = "none"; // IE
                                    // (Untuk webkit kita tambahkan style di tag CSS di atas)

                                    const dummyContent = document.createElement("div");
                                    dummyContent.style.height = "1px";
                                    topScroll.appendChild(dummyContent);

                                    // Masukkan sebelum container tabel
                                    container.parentElement.insertBefore(topScroll, container);

                                    const syncWidths = () => {
                                        dummyContent.style.width = container.scrollWidth + "px";
                                        // Sembunyikan top scrollbar jika tidak ada scroll
                                        if (container.scrollWidth <= container.clientWidth) {
                                            topScroll.style.display = "none";
                                        } else {
                                            topScroll.style.display = "block";
                                        }
                                    };

                                    // Sinkronisasi ukuran
                                    syncWidths();
                                    const ro = new ResizeObserver(syncWidths);
                                    ro.observe(container);
                                    // Objek di dalam tabel mungkin berubah
                                    const table = container.querySelector("table");
                                    if(table) ro.observe(table);

                                    // Sinkronisasi scroll
                                    topScroll.addEventListener("scroll", () => {
                                        container.scrollLeft = topScroll.scrollLeft;
                                    });
                                    container.addEventListener("scroll", () => {
                                        topScroll.scrollLeft = container.scrollLeft;
                                    });
                                });
                            }

                            // Jalankan inisialisasi
                            initTopScrollbars();

                            // Gunakan MutationObserver untuk mendeteksi perubahan Livewire (tabel di-load ulang)
                            const observer = new MutationObserver((mutations) => {
                                for (let mutation of mutations) {
                                    if (mutation.addedNodes.length) {
                                        initTopScrollbars();
                                    }
                                }
                            });
                            observer.observe(document.body, { childList: true, subtree: true });
                        });
                    </script>
                