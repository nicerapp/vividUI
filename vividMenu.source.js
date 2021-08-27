class naVividMenu {
    constructor(el, callback) {
        var t = this;
        t.el = el;
        t.displayedOnSmallPhone = (na.m.userDevice.isPhone && $(window).width() < 220 + 180 + 15);
        if (t.displayedOnSmallPhone) $(t.el).css({ width : $(window).width()-(3*60)-5 });
        t.t = $(el).attr('theme');
        t.type = $(el).attr('type');
        t.items = [];
        t.initItems();
        t.onresize(t, {}, callback);
        t.updateItemStates();
    }
    
    initItems() {
        var t = this;
        $(t.el).find('li').each(function(idx,li) {
            var html = '<div id="'+t.el.id+'_'+idx+'" class="vividButton" theme="'+t.t+'" style="display:none">'+$(li).children('a')[0].outerHTML+'</div>';
            t.items[idx] = {
                li : li,
                b : new naVividButton(null,html,t.el),
                level : jQuery(li).parents('ul').length,
                path : ''
            };
            var it = t.items[idx];
            if (t.displayedOnSmallPhone) $(it.b.el).css({ width : $(window).width()-(3*60)-15 });
            if (it.level===1) $(it.b.el).addClass('level1');            
            li.it = it;            
            $('#'+it.b.el.id/*+'::before'*/).bind('mouseover', function() {
                t.onmouseover(it);
            });
            $('#'+it.b.el.id/*+'::before'*/).bind('mouseout', function() {
                t.onmouseout(it);
            });
            $('#'+it.b.el.id/*+'::before'*/).bind('click', function() {
                t.onclick(it);
            });
            $(li).parents('ul').each(function(idx2,ul){
                var it = t.items[idx];
                for (var i=0; i<t.items.length; i++) {
                    var it2 = t.items[i];
                    if ($(ul).parents('li')[0]===it2.li) {
                        if (it.path!=='') it.path += ',';
                        it.path += '#'+it2.b.el.id;
                        if (!it.p) it.p = it2.li;
                        if (!it.parent) it.parent = i;
                        break;
                    }
                }
            });
        });
        //debugger;
    }
    
    updateItemStates() {
        var t = this;
        $(this.el).find('li > a').each(function(idx,li) {
            let 
            isc = $(li).attr('vividMenu_isSelected_condition');
            
            if (isc) {
                var 
                menuItem = t.items[idx].b.el,
                r = eval(isc);
                
                if (r)
                    $(menuItem).addClass('vividButtonSelected').removeClass('vividButton');
                else
                    $(menuItem).removeClass('vividButtonSelected').addClass('vividButton')
            }
        });
    }
    
    onresize(t, levels, callback) {
        var
        windowWidth = $(window).width(),
        windowHeight = $(window).height();
    
        if (!t) t = this;
        if (!levels) levels = {};
        
        if (!t.resizeDoingIdx) t.resizeDoingIdx=0;
        if (!t.resizeDoneCount) t.resizeDoneCount=0;
        t.resizeDoneCount++;
        if (t.resizeDoneCount>25) {
            setTimeout(function() {
                t.resizeDoneCount = 0;
                t.onresize(t, levels, callback);
            }, 200);
        } else {
            if (t.resizeDoingIdx>=t.items.length) {
                t.resizeDoingIdx = 0;
                t.resizeDoneCount = 0;
                if (typeof callback=='function') callback(t);
            } else {
                let it = t.items[t.resizeDoingIdx];
                it.label = $(it.b.el).children('a').html();
                it.pul = $(it.li).parents('ul')[0];
                
                $(it.pul).children('li').each(function(idx,li) {
                    if (it.li === li) {
                        it.levelIdx = idx;
                    }
                });
                
                $(it.b.el).css({display:'flex'});
                
                let 
                parent = t.items[it.parent],
                l = levels['path '+it.path],
                placing = 'right',
                placingVertical = 'bottom',
                left = jQuery(it.b.el).offset().left - (parent ? parent.offsetX : $(it.b.el).width()),
                right = (windowWidth - jQuery(it.b.el).offset().left /* - ($(it.b.el).width() * 0.7)*/ - (parent ? parent.offsetX : $(it.b.el).width())),
                top = jQuery(it.b.el).offset().top + (parent ? parent.offsetY : $(it.b.el).height()),
                bottom = (windowHeight - jQuery(it.b.el).offset().top /* - ($(it.b.el).width() * 0.7)*/ - (parent ? parent.offsetY : $(it.b.el).height()));
                
                //debugger;
                
                if (left > right) placing = 'left';
                if (placing=='left') var width = left; else var width = right;
                if (top > bottom) placingVertical = 'top';
                if (placingVertical=='top') var height = top; else var height = bottom;
                //if (it.label=='HD Video') debugger;
                if (t.type=='verticalMenu') {
                    t.onresize_vertical (t, it, levels, callback, windowHeight, windowWidth, parent, l, placingVertical, height, top, bottom, placing, width, left, right);
                } else {
                    t.onresize_horizontal (t, it, levels, callback, windowHeight, windowWidth, parent, l, placing, width, left, right, placingVertical, height, top, bottom);
                }
            }
        }
        //debugger;
    }

    onresize_vertical(t, it, levels, callback, windowHeight, windowWidth, parent, l, placingVertical, height, top, bottom, placing, width, left, right ) {
                let
                pl = null,
                //columnCount = Math.floor(((placingVertical=='bottom'?bottom:top)-($(it.b.el).width()/3)) / $(it.b.el).width()),
                rowCount =  Math.floor(((placingVertical=='bottom'?bottom:top)-($(it.b.el).height()/3)) / $(it.b.el).height()),
                itemsOnLevelCount = 0;
                
                for (let j=0; j<t.items.length; j++) {
                    let it2 = t.items[j];
                    if (it2.parent === it.parent && it2.level === it.level) {
                        itemsOnLevelCount++;
                        //debugger;
                    }
                };
                
                let 
                columnCount = Math.floor(Math.sqrt(itemsOnLevelCount)),
                column = 0,
                columnIdx = 0,
                row = 0,
                rowIdx = 0;
                rowCount = Math.ceil(itemsOnLevelCount / columnCount);
                
                l = levels['path '+it.path];
                if (!l) {
                    if (!parent || !levels['path '+parent.path]) {
                        pl = {
                            offsetX : 0,
                            offsetY : placingVertical=='bottom'?7:-7,
                            zIndexOffset : 0,
                            row : 0,
                            column : 0
                        }
                    } else {
                        pl = levels['path '+parent.path];
                    }
                    
                    let zof = pl.zIndexOffset + 1;
                    levels['path '+it.path] = jQuery.extend({}, pl);
                    levels['path '+it.path].offsetX = pl.offsetX;
                    levels['path '+it.path].offsetY = pl.offsetY;
                    levels['path '+it.path].zIndexOffset = zof;
                    levels['path '+it.path].row = 0;
                    levels['path '+it.path].column = 0;
                    l = levels['path '+it.path];
                };
                
                let row1 = l.row;
                for (let j=0; j<t.items.length; j++) {
                    let it2 = t.items[j];
                    if (it2.parent === it.parent && it2.level === it.level) {
                        //debugger;
                        if (column >= columnCount) {
                            row = l.row + 1;
                            column = 1;
                        } else {
                            column++;
                            row = row1;
                        }
                        //if ($('a',it.b.el)[0].innerHTML=='Dark mode') debugger;
                        //if ($('a',it.b.el)[0].innerHTML=='Landscape') debugger;
                        if (it2.b.el.id === it.b.el.id) break;
                    } 
                };
                l.row = row;
                l.column = column;
                //if (it.level == 3 && t.items[it.parent].label=='Landscape') debugger;
                
                it.childrenPlacement = placingVertical;
                it.columnIdx = columnIdx;
                it.column = column;
                it.rowIdx = rowIdx;
                it.row = row;
                $(it.b.el).css({ height : 10 });
                it.offsetY = (
                    it.level === 1
                    ? ( ($(it.b.el).height() + 20) * it.levelIdx )
                    : l
                        ? it.level === 2
                            ? placingVertical==='top'
                                ? l.offsetY + parent.offsetY - ( ($(it.b.el).height()+18) * it.row) 
                                : l.offsetY + parent.offsetY + ( ($(it.b.el).height()+18) * it.row) 
                            : placingVertical==='top'
                                ? l.offsetY + parent.offsetY - ( ($(it.b.el).height()+18) * it.row) - ($(it.b.el).height()+2)
                                : l.offsetY + parent.offsetY + ( ($(it.b.el).height()+18) * it.row) + ($(it.b.el).height()+2)
                        : it.level === 2
                            ? placingVertical==='top'
                                ? parent.offsetY - ( ($(it.b.el).height()+18) * it.row)
                                : parent.offsetY + ( ($(it.b.el).height()+18) * it.row) 
                            : placingVertical==='top'
                                ? parent.offsetY - ( ($(it.b.el).height()+18) * it.row) - ($(it.b.el).height()*2)
                                : parent.offsetY + ( ($(it.b.el).height()+18) * it.row) + ($(it.b.el).height()*2)
                );
                it.offsetX = (
                    it.level === 1
                    ? 0
                    : placing === 'left'
                        ? parent.offsetX - ( ($(it.b.el).width()+15) * (it.column-1) ) - (($(it.b.el).width()/4)*3)
                        : parent.offsetX + ( ($(it.b.el).width()+15) * (it.column-1) ) + (($(it.b.el).width()/4)*3)
                );
                
                it.zIndex = (100 * 1000) + l.zIndexOffset;

                $(it.b.el).css({
                    left : it.offsetX,
                    top : it.offsetY,
                    zIndex : it.zIndex,
                    display : (it.level===1?'flex':'none')
                });
                
                t.resizeDoingIdx++;
                setTimeout (function(){t.onresize(t, levels, callback)}, 10);
    }
    
    
    onresize_horizontal(t, it, levels, callback, windowHeight, windowWidth, parent, l, placing, width, left, right) {
                var
                pl = null,
                columnCount = Math.floor((windowWidth-($(it.b.el).width()/3)) / $(it.b.el).width()),
                itemsOnLevelCount = 0;
                
                for (var j=0; j<t.items.length; j++) {
                    var it2 = t.items[j];
                    if (it2.parent === it.parent && it2.level === it.level) itemsOnLevelCount++;
                };
                
                var
                rowCount = Math.ceil(itemsOnLevelCount / columnCount);
                
                if (it.level===0) {
                    columnCount = 1;
                    rowCount = 9999;
                } else if (columnCount > rowCount) {
                    columnCount = Math.floor(Math.sqrt(itemsOnLevelCount));
                    rowCount = Math.ceil(itemsOnLevelCount / columnCount);
                };

                var
                column = 0,
                columnIdx = 1;
                for (var j=0; j<t.items.length; j++) {
                    var it2 = t.items[j];
                    if (it2.parent === it.parent && it2.level === it.level) {
                        if ((it.levelIdx+1) <= (column * rowCount) + columnIdx ) {
                            //columnIdx--;
                        } else if (columnIdx >= rowCount) {
                            column++;
                            columnIdx = 1;
                        } else columnIdx++;
                    } 
                    
                };
                
                var           
                l = levels['path '+it.path];
                it.childrenPlacement = placing;
                it.columnIdx = columnIdx;
                it.column = column;
                it.offsetX = (
                    it.level === 1
                    ? ($(it.b.el).width() + 20) * it.levelIdx
                    : l
                        ? it.level === 2
                            ? placing==='right'
                                ? l.offsetX + parent.offsetX + ( ($(it.b.el).width()+20) * it.column) 
                                : l.offsetX + parent.offsetX - ( ($(it.b.el).width()+20) * it.column) 
                            : placing==='right'
                                ? l.offsetX + parent.offsetX + ( ($(it.b.el).width()+20) * it.column) + (($(it.b.el).width()/4)*3)
                                : l.offsetX + parent.offsetX - ( ($(it.b.el).width()+20) * it.column) - (($(it.b.el).width()/4)*3)
                        : it.level === 2
                            ? placing==='right'
                                ? parent.offsetX + ( ($(it.b.el).width()+20) * it.column) 
                                : parent.offsetX - ( ($(it.b.el).width()+20) * it.column) 
                            : placing==='right'
                                ? parent.offsetX + ( ($(it.b.el).width()+20) * it.column) +  (($(it.b.el).width()/4)*3)
                                : parent.offsetX - ( ($(it.b.el).width()+20) * it.column) -  (($(it.b.el).width()/4)*3)
                );
                it.offsetY = (
                    it.level === 1
                    ? 0
                    : it.level === 2
                        ? parent.offsetY + ( ($(it.b.el).height()+20) * it.columnIdx )
                        : parent.offsetY + ( ($(it.b.el).height()+20) * (it.columnIdx-1) )+ ($(it.b.el).height())
                );
                
                if (!l) {
                    if (!parent || !levels['path '+parent.path]) {
                        pl = {
                            offsetX : 0,
                            offsetY : 0,
                            zIndexOffset : 0
                        }
                    } else {
                        pl = levels['path '+parent.path];
                    }
                    
                    var zof = pl.zIndexOffset + 1;
                    levels['path '+it.path] = jQuery.extend({}, pl);
                    levels['path '+it.path].offsetX = pl.offsetX;
                    levels['path '+it.path].offsetY = pl.offsetY;
                    levels['path '+it.path].zIndexOffset = zof;
                    l = levels['path '+it.path];
                };
                it.zIndex = (100 * 1000) + l.zIndexOffset;

                $(it.b.el).css({
                    left : it.offsetX,
                    top : it.offsetY,
                    zIndex : it.zIndex,
                    display : (it.level===1?'flex':'none')
                });
                
                t.resizeDoingIdx++;
                setTimeout (function(){t.onresize(t, levels, callback)}, 10);
    }
    
    onmouseover(it) {
        var
        t = this,
        opLevMax = 1,
        opLevMin = 0.2;
        
        if (it.p) it.p.it.travelledIntoChild = true;
        if (it.level===1) for (var i=0; i<t.items.length; i++) {
            var it2 = t.items[i];
            if (it2.li.openChildren) it2.li.openChildren.each(function(idx,cli){
                $(cli.it.b.el).fadeOut('fast');
            });
        }
        if (t.timeoutMouseout) clearTimeout (t.timeoutMouseout);
        if (t.timeoutMouseover) clearTimeout (t.timeoutMouseover);
        t.timeoutMouseover = setTimeout (function() {
            $(it.p).find('li').each(function(idx,pcli){
                //if (pcli!==it.li) {
                    if (pcli.openChildren) pcli.openChildren.each(function(idx2,li) {
                        $(li.it.b.el).fadeOut('fast');
                        if (li.openChildren) li.openChildren.each(function(idx3,li2){
                            $(li2.it.b.el).fadeOut('fast');
                        });
                    });
                //}
            });
            
            it.li.openChildren = $(it.li).children('ul').children('li');
            var hasChildren = false;
            it.li.openChildren.each(function(idx,li) {
                var
                opLev = opLevMin + (
                    ( (opLevMax-opLevMin) / ((li.it.level-it.level)) )
                );
                if (li.it.level!==it.level) {
                    if ($(li.it.b.el).css('display')==='none') $(li.it.b.el).css({display:'flex',opacity:0});
                    $(li.it.b.el).stop(true,true).delay(20).animate({opacity:opLev},'fast');
                    hasChildren = true;
                }
            });
            
            $(it.li).parents('ul').each(function(idx,pul) {
                //debugger;
                if (idx<$(it.li).parents('ul').length-1) $(pul).children('li').each(function(idx2,cli){
                    var
                    opLevFactor =
                        (opLevMax-opLevMin) / (cli.it.level + idx)*10//(cli.it.level /*>*/<= $(it.li).parents('ul').length ? cli.it.level * 2 : (((opLevMax-opLevMin)*10*(idx+2))-2)*5),
                    opLev = 
                        opLevMax - (
                            (opLevMax-opLevMin) / ((opLevFactor + cli.it.level) / 4)
                        );
                        
                        
                    //if (cli.it.label=='Dark mode'||cli.it.label=='Anime') debugger;
                    if (opLev >= 0 && opLev <= 1) {
                        if ($(cli.it.b.el).css('display')==='none') $(cli.it.b.el).css({display:'flex',opacity:0});
                        $(cli.it.b.el).stop(true,true).delay(20).animate({opacity:opLev},'fast');
                    }
                });
                
                if (idx===0 && !hasChildren) $(pul).children('li').each(function(idx,cli){
                    if ($(cli.it.b.el).css('display')==='none') $(cli.it.b.el).css({display:'flex',opacity:0});
                    $(cli.it.b.el).stop(true,true).delay(20).animate({opacity:1},'fast');
                });
            });

            $(it.path).stop(true,true).animate ({opacity:1},'fast');
            $('#'+it.b.el.id).stop(true,true).delay(20).animate ({opacity:1},'fast');
            var opLev = null, opLev2 = null;
            if (it.travelledIntoChild && it.parent) {
                $(it.p).children('ul').children('li').each(function(idx3,li3) {
                    opLev = opLevMin + (
                        ( (opLevMax-opLevMin) / ((li3.it.level-it.p.it.level+1)) )
                    );
                    if (li3.it.level===it.level) {
                        if ($(li3.it.b.el).css('display')==='none') $(li3.it.b.el).css({display:'flex',opacity:0});
                        $(li3.it.b.el).stop(true,true).delay(20).animate({opacity:opLev},'fast');
                        hasChildren = true;
                    }
                });
                t.items[it.parent].li.openChildren.each(function(idx,el){
                    if ($(el.it.b.el).css('display')==='none') $(el.it.b.el).css({display:'flex',opacity:0});
                    opLev2 = (
                        el === it.li
                        ? 1
                        : opLev
                    );
                    $(el.it.b.el).stop(true,true).delay(20).animate ({opacity:opLev2},'fast');
                });
                $(it.li).children('ul').children('li').each(function(idx2,el2){
                    if ($(el2.it.b.el).css('display')==='none') $(el2.it.b.el).css({display:'flex',opacity:0});
                    $(el2.it.b.el).stop(true,true).delay(20).animate ({opacity:1},'fast');
                });
                delete it.travelledIntoChild;
            }
        }, 250);
    }
    
    onmouseout(it) {
        var
        t = this;
        if (t.timeoutMouseout) clearTimeout (t.timeoutMouseout);
        t.timeoutMouseout = setTimeout (function() {
            for (var i=0; i<t.items.length; i++) {
                var it2 = t.items[i];
                if (it2.li.openChildren) it2.li.openChildren.each(function(idx,li) {
                    $(li.it.b.el).fadeOut('slow');
                });
                if (it2.level===1) {
                    $(it2.b.el).animate({opacity:1},'fast');
                }
            }
        }, 1000);
    }

    onclick(it) {
        var a = $(it.b.el).children('a');
        if (
            typeof a.attr('windowName') == 'string'
            && a.attr('windowName')!==''
        ) {
            window.open(a.attr('href'),a.attr('windowName')).focus();
        } else {
            var href = a.attr('href');
            if (href.match(/javascript:/)) eval(href.replace('javascript:','')); else window.location.href = href;
        }
    }
}
