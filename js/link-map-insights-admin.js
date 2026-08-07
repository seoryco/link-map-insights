jQuery(document).ready(function ($) {
    var __ = ( window.wp && window.wp.i18n && window.wp.i18n.__ ) ? window.wp.i18n.__ : function ( text ) { return text; };
    var sprintf = ( window.wp && window.wp.i18n && window.wp.i18n.sprintf ) ? window.wp.i18n.sprintf : function ( format ) {
        var args = Array.prototype.slice.call( arguments, 1 );
        var i = 0;
        return format.replace( /%(\d+\$)?[ds]/g, function () { return args[ i++ ]; } );
    };

    let nodes = new vis.DataSet();
    let edges = new vis.DataSet();
    let network = null;
    let originalEdges = [];

    function filterNodes() {
        if (nodes.length === 0) { return; }
        var selectedPostTypes = $('.lmi-post-type-filter:checked').map(function () {
            return $(this).val();
        }).get();
        var selectedTermKeys = $('.term-filter:checked').map(function () {
            return $(this).val();
        }).get();
        var nodesToUpdate = nodes.get().filter(function (node) {
            var hideByType = selectedPostTypes.indexOf(node.post_type) === -1;
            var hasTerms = node.terms && node.terms.length > 0;
            var hideByTerm = hasTerms && !node.terms.some(function (t) { return selectedTermKeys.indexOf(t) !== -1; });
            var shouldBeHidden = hideByType || hideByTerm;
            return node.hidden !== shouldBeHidden;
        }).map(function (node) {
            var hideByType = selectedPostTypes.indexOf(node.post_type) === -1;
            var hasTerms = node.terms && node.terms.length > 0;
            var hideByTerm = hasTerms && !node.terms.some(function (t) { return selectedTermKeys.indexOf(t) !== -1; });
            return {
                id: node.id,
                hidden: hideByType || hideByTerm
            };
        });
        nodes.update(nodesToUpdate);
    }

    function fetchDataset(regenerate) {
        var $bar = $('#loadingBar');
        var $text = $('#loadingBar #text');
        $bar.css({ display: 'block', opacity: 1 });
        if (regenerate) {
            $text.text('0%');
        } else {
            $text.text( __( 'Loading…', 'link-map-insights' ) );
        }
        var postTypes = $('.lmi-post-type-filter:checked').map(function () { return $(this).val(); }).get();
        var payload = {
            action: 'seoryco_lmi_get_dataset',
            lmi_nonce: lmiData.nonce
        };
        if (postTypes.length) {
            payload.post_types = postTypes;
        }
        if (regenerate) {
            payload.lmi_regenerate = '1';
        }
        $.ajax({
            url: lmiData.ajax_url,
            type: 'POST',
            data: payload,
            traditional: true
        })
            .done(function (response) {
                if (response && response.success === false && response.data) {
                    $bar.css('display', 'none');
                    $text.text(response.data.message || __( 'Error', 'link-map-insights' ));
                    return;
                }
                if (!response || !Array.isArray(response.nodes) || !Array.isArray(response.edges)) {
                    $bar.css('display', 'none');
                    if (regenerate) {
                        $text.text( __( 'Invalid data format', 'link-map-insights' ) );
                    }
                    if (typeof console !== 'undefined' && console.error) {
                        console.error('LMI response:', response);
                    }
                    return;
                }
                if (response.nodes.length === 0 && response.edges.length === 0) {
                    $bar.css('display', 'none');
                    return;
                }
                try {
                    if (regenerate) {
                        $text.text( __( 'Rendering…', 'link-map-insights' ) );
                    }
                    draw(response, regenerate);
                    if (!regenerate) {
                        $bar.css('display', 'none');
                    }
                } catch (err) {
                    $bar.css('display', 'none');
                    if (regenerate) {
                        $text.text( __( 'Rendering error', 'link-map-insights' ) );
                        alert( __( 'An error occurred while rendering: ', 'link-map-insights' ) + (err.message || err));
                    }
                    if (typeof console !== 'undefined' && console.error) {
                        console.error('LMI draw error:', err);
                    }
                }
            })
            .fail(function (xhr) {
                $bar.css('display', 'none');
                var msg = __( 'The request failed.', 'link-map-insights' );
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    msg = xhr.responseJSON.data.message;
                } else if (xhr.status === 403) {
                    msg = __( 'You do not have permission.', 'link-map-insights' );
                }
                $text.text(msg);
                if (regenerate) {
                    alert(msg);
                }
            });
    }

    function draw(response, showLoadingProgress) {
        nodes.clear();
        edges.clear();
        originalEdges = response.edges;

        let linkCounts = {};
        response.edges.forEach(edge => {
            if (linkCounts[edge.to]) {
                linkCounts[edge.to]++;
            } else {
                linkCounts[edge.to] = 1;
            }
        });

        response.nodes.forEach(node => {
            let size = 20 + (linkCounts[node.id] ? linkCounts[node.id] * 5 : 0);
            var bg = node.color || '#fff';
            var fontColor = (node.font && node.font.color) ? node.font.color : '#000';
            nodes.add({
                id: node.id,
                label: $('#toggle-title-length').is(':checked') ? node.short_label : node.full_label,
                full_label: node.full_label,
                short_label: node.short_label,
                url: node.url,
                post_type: node.post_type,
                terms: node.terms || [],
                value: size,
                color: {
                    background: bg,
                    border: '#000000'
                },
                font: { color: fontColor },
                fixed: false,
                _lmiOriginalBg: bg,
                _lmiOriginalFontColor: fontColor
            });
        });

        response.edges.forEach(edge => {
            edges.add({
                from: edge.from,
                to: edge.to,
                arrows: edge.arrows
            });
        });

        var rawTerms = response.terms_colors || response.categories_colors;
        var termsColors = (rawTerms && typeof rawTerms === 'object' && !Array.isArray(rawTerms)) ? rawTerms : {};
        $('#term-filters').empty();
        var groups = {};
        Object.keys(termsColors).forEach(function (key) {
            var info = termsColors[key];
            if (!info || typeof info !== 'object') { return; }
            var groupName = (info.taxonomy_label && info.taxonomy_label.toString()) ? info.taxonomy_label : __( 'Other', 'link-map-insights' );
            if (!groups[groupName]) { groups[groupName] = []; }
            groups[groupName].push({ key: key, info: info });
        });
        var groupOrder = ['カテゴリー', '投稿タグ', 'タグ', 'カテゴリ', 'Post Tag'];
        Object.keys(groups).sort(function (a, b) {
            var ia = groupOrder.indexOf(a);
            var ib = groupOrder.indexOf(b);
            if (ia !== -1 && ib !== -1) { return ia - ib; }
            if (ia !== -1) { return -1; }
            if (ib !== -1) { return 1; }
            return a.localeCompare(b);
        }).forEach(function (groupName) {
            var terms = groups[groupName];
            var $section = $('<div class="lmi-term-group"></div>');
            var $title = $('<div class="lmi-term-group-title"></div>');
            $title.append($('<span></span>').text(groupName));
            $title.append(
                $('<span class="lmi-term-group-buttons"></span>').append(
                    $('<button type="button" class="lmi-btn-select-all"></button>').text( __( 'Select all', 'link-map-insights' ) ),
                    $('<button type="button" class="lmi-btn-deselect-all"></button>').text( __( 'Deselect all', 'link-map-insights' ) )
                )
            );
            $section.append($title);
            var $inner = $('<div class="lmi-term-group-inner"></div>');
            terms.forEach(function (item) {
                var info = item.info;
                var labelText = (info.name && info.name.toString()) ? info.name : item.key;
                var color = (info.color) ? info.color : '#ddd';
                var $label = $('<label></label>').append(
                    $('<input>', { type: 'checkbox', class: 'term-filter', value: item.key, checked: true }),
                    $('<span>', { class: 'category-color' }).css('background-color', color),
                    document.createTextNode(' ' + labelText)
                );
                $inner.append($label).append(' ');
            });
            $section.append($inner);
            $('#term-filters').append($section);
        });

        const container = document.getElementById('mynetwork');
        const data = { nodes: nodes, edges: edges };
        const options = {
            physics: {
                enabled: true,
                repulsion: {
                    centralGravity: 0.1,
                    springLength: 250,
                    springConstant: 0.05,
                    nodeDistance: 250,
                    damping: 0.09
                },
                solver: 'repulsion'
            },
            nodes: {
                shape: 'box',
                scaling: {
                    min: 10,
                    max: 100,
                    label: {
                        min: 14,
                        max: 30,
                        drawThreshold: 1,
                        maxVisible: 20
                    }
                },
                font: {
                    size: 12,
                    face: 'arial',
                    color: '#343434'
                }
            },
            edges: {
                color: { color: '#000000' }
            }
        };
        network = new vis.Network(container, data, options);

        function hideLoadingBar() {
            var bar = document.getElementById('loadingBar');
            if (bar) {
                var textEl = bar.querySelector('#text');
                if (textEl) { textEl.innerHTML = '100%'; }
                bar.style.opacity = 0;
                setTimeout(function () { bar.style.display = 'none'; }, 500);
            }
            network.setOptions({ physics: { enabled: false } });
        }

        if (response.nodes.length === 0) {
            if (showLoadingProgress) { hideLoadingBar(); }
            else { network.setOptions({ physics: { enabled: false } }); }
        } else if (showLoadingProgress) {
            network.on("stabilizationProgress", function (params) {
                var widthFactor = params.iterations / params.total;
                var percent = Math.round(widthFactor * 100);
                var textEl = document.getElementById('text');
                if (textEl) { textEl.innerHTML = percent + '%'; }
            });
            network.once("stabilizationIterationsDone", function () {
                hideLoadingBar();
            });
        } else {
            network.once("stabilizationIterationsDone", function () {
                network.setOptions({ physics: { enabled: false } });
            });
        }

        $('#toggle-title-length').change(function () {
            const isChecked = $(this).is(':checked');
            nodes.update(nodes.get().map(node => ({
                id: node.id,
                label: isChecked ? node.short_label : node.full_label
            })));
        });

        $('.lmi-post-type-filter').change(function () {
            filterNodes();
        });
        $(document).off('change.lmiTermFilter').on('change.lmiTermFilter', '.term-filter', function () {
            filterNodes();
        });

        network.on('click', function (params) {
            if (params.nodes.length > 0) {
                let selectedNodeId = params.nodes[0];
                let connectedNodes = network.getConnectedNodes(selectedNodeId);
                let allNodes = nodes.get({ returnType: "Object" });

                nodes.update(Object.values(allNodes).map(node => ({
                    id: node.id,
                    hidden: !connectedNodes.includes(node.id) && node.id !== selectedNodeId
                })));

                let allEdges = edges.get({ returnType: "Object" });

                edges.update(Object.values(allEdges).map(edge => ({
                    id: edge.id,
                    hidden: !connectedNodes.includes(edge.to) && !connectedNodes.includes(edge.from)
                })));
            }
        });

        network.on('doubleClick', function (params) {
            if (params.nodes.length === 0) {
                filterNodes();
                edges.update(edges.get().map(edge => ({
                    id: edge.id,
                    hidden: false
                })));
            } else if (params.nodes.length === 1) {
                let node = nodes.get(params.nodes[0]);
                if (node && node.url) {
                    window.open(node.url, '_blank');
                }
            }
        });

        var postTypeLabels = (response.post_type_labels && typeof response.post_type_labels === 'object') ? response.post_type_labels : {};
        function renderZeroLinksTable(containerId, list, totalTitleId, totalTitleFormat) {
            var itemsList = list && Array.isArray(list) ? list : [];
            var total = itemsList.length;
            $('#' + totalTitleId).text(sprintf(totalTitleFormat, total));
            var $container = $('#' + containerId).empty();
            if (total === 0) {
                return;
            }
            var groups = {};
            itemsList.forEach(function (item) {
                var pt = (item.post_type != null) ? String(item.post_type) : 'post';
                if (!groups[pt]) { groups[pt] = []; }
                groups[pt].push(item);
            });
            var order = ['post', 'page'];
            Object.keys(groups).sort(function (a, b) {
                var ia = order.indexOf(a);
                var ib = order.indexOf(b);
                if (ia !== -1 && ib !== -1) { return ia - ib; }
                if (ia !== -1) { return -1; }
                if (ib !== -1) { return 1; }
                return a.localeCompare(b);
            }).forEach(function (pt) {
                var items = groups[pt];
                var label = postTypeLabels[pt] || pt;
                var $section = $('<div class="lmi-zero-links-group"></div>');
                /* translators: 1: post type label, 2: number of pages. */
                $section.append($('<h3 class="lmi-zero-links-group-title"></h3>').text(sprintf( __( '%1$s: %2$d', 'link-map-insights' ), label, items.length )));
                var $table = $('<table class="wp-list-table widefat fixed striped"></table>').append(
                    $('<thead></thead>').append($('<tr></tr>').append(
                        $('<th></th>').text( __( 'ID', 'link-map-insights' ) ),
                        $('<th></th>').text( __( 'Page title', 'link-map-insights' ) ),
                        $('<th></th>').text( __( 'Edit', 'link-map-insights' ) ),
                        $('<th></th>').text( __( 'View', 'link-map-insights' ) )
                    )),
                    $('<tbody></tbody>')
                );
                var $tbody = $table.find('tbody');
                items.forEach(function (post) {
                    var $tr = $('<tr></tr>').append(
                        $('<td></td>').text(post.id),
                        $('<td></td>').text(post.title),
                        $('<td></td>').append($('<button>', { type: 'button', class: 'lmi-btn-edit', 'data-url': post.edit_url || '' }).text( __( 'Edit', 'link-map-insights' ) )),
                        $('<td></td>').append($('<button>', { type: 'button', class: 'lmi-btn-view', 'data-url': post.view_url || '' }).text( __( 'View', 'link-map-insights' ) ))
                    );
                    $tbody.append($tr);
                });
                $section.append($table);
                $container.append($section);
            });
        }
        /* translators: %d: number of pages. */
        renderZeroLinksTable('no-inbound-links-container', response.no_inbound_links || [], 'no-inbound-links-title', __( 'Pages with no inbound links: %d', 'link-map-insights' ));
        /* translators: %d: number of pages. */
        renderZeroLinksTable('no-outbound-links-container', response.no_outbound_links || [], 'no-outbound-links-title', __( 'Pages with no outbound links: %d', 'link-map-insights' ));

        $(document).off('click.lmiTable').on('click.lmiTable', '.lmi-btn-edit, .lmi-btn-view', function () {
            var url = $(this).data('url');
            if (url) {
                window.open(url, '_blank');
            }
        });
    }

    if (lmiData.cached_dataset && Array.isArray(lmiData.cached_dataset.nodes) && Array.isArray(lmiData.cached_dataset.edges) && lmiData.cached_dataset.nodes.length > 0) {
        var $bar = $('#loadingBar');
        var $text = $('#loadingBar #text');
        $bar.css({ display: 'block', opacity: 1 });
        $text.text('0%');
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                try {
                    draw(lmiData.cached_dataset, true);
                } catch (err) {
                    $bar.css('display', 'none');
                    if (typeof console !== 'undefined' && console.error) {
                        console.error('LMI draw error:', err);
                    }
                }
            });
        });
    } else {
        fetchDataset(false);
    }

    $('#generate-link-map').on('click', function () {
        fetchDataset(true);
    });

    $(document).on('click.lmiSelectAll', '.lmi-btn-select-all', function () {
        var target = $(this).data('target');
        var $checkboxes = target ? $(target) : $(this).closest('.lmi-term-group').find('.term-filter');
        $checkboxes.prop('checked', true);
        filterNodes();
    });
    $(document).on('click.lmiDeselectAll', '.lmi-btn-deselect-all', function () {
        var target = $(this).data('target');
        var $checkboxes = target ? $(target) : $(this).closest('.lmi-term-group').find('.term-filter');
        $checkboxes.prop('checked', false);
        filterNodes();
    });

    $('#search-button').on('click', function() {
        executeSearch();
    });

    $('#node-search').on('keypress', function(e) {
        if (e.which == 13) { // Enter key pressed
            executeSearch();
        }
    });

    $('#reset-search-button').on('click', function() {
        $('#node-search').val('');
        var nodesToUpdate = nodes.get().map(function (node) {
            var bg = node._lmiOriginalBg != null ? node._lmiOriginalBg : (node.color && node.color.background) ? node.color.background : '#fff';
            var fontColor = node._lmiOriginalFontColor != null ? node._lmiOriginalFontColor : (node.font && node.font.color) ? node.font.color : '#000';
            return {
                id: node.id,
                color: { background: bg, border: '#000000' },
                font: { color: fontColor },
                borderWidth: 1,
                borderWidthSelected: 1
            };
        });
        nodes.update(nodesToUpdate);
    });

    var LMI_SEARCH_DIMMED_BG = '#d1d5db';
    var LMI_SEARCH_DIMMED_FONT = '#9ca3af';

    function executeSearch() {
        if (!network) { return; }
        var searchTerm = ($('#node-search').val() || '').toString().trim().toLowerCase();
        var searchTerms = searchTerm ? searchTerm.split(/[\s\u3000]+/) : [];
        var allNodes = nodes.get();
        if (allNodes.length === 0) { return; }
        var nodesToUpdate = allNodes.map(function (node) {
            var labelText = (node.label != null ? node.label : (node.full_label != null ? node.full_label : node.short_label || ''));
            if (typeof labelText !== 'string') { labelText = String(labelText); }
            var labelLower = labelText.toLowerCase();
            var match = searchTerms.length > 0 && searchTerms.every(function (term) { return term === '' || labelLower.indexOf(term) !== -1; });
            var bg = match ? (node._lmiOriginalBg != null ? node._lmiOriginalBg : (node.color && node.color.background) ? node.color.background : '#fff') : LMI_SEARCH_DIMMED_BG;
            var fontColor = match ? (node._lmiOriginalFontColor != null ? node._lmiOriginalFontColor : (node.font && node.font.color) ? node.font.color : '#000') : LMI_SEARCH_DIMMED_FONT;
            return {
                id: node.id,
                borderWidth: match ? 2 : 1,
                borderWidthSelected: match ? 2 : 1,
                color: {
                    background: bg,
                    border: match ? '#dc2626' : '#9ca3af'
                },
                font: { color: fontColor }
            };
        });
        nodes.update(nodesToUpdate);
    }

    // 拡大・縮小ボタンのクリックイベントを処理
    $('#zoom-in').on('click', function () {
        zoomNetwork(1.2); // 1.2倍に拡大
    });

    $('#zoom-out').on('click', function () {
        zoomNetwork(0.8); // 0.8倍に縮小
    });

    function zoomNetwork(scale) {
        const scaleOptions = {
            scale: scale,
            animation: {
                duration: 300,
                easingFunction: "easeInOutQuad"
            }
        };
        const currentPosition = network.getViewPosition();
        network.moveTo({
            position: currentPosition,
            scale: network.getScale() * scaleOptions.scale,
            animation: scaleOptions.animation
        });
    }
});
