(() => {
    const initial=window.kontrakSheet;
    if(!initial)return;
    let rows=JSON.parse(JSON.stringify(initial.rows));let counter=0;let active={row:0,col:4};let timer;let version=0;let dirty=false;
    const body=document.querySelector('#sheet_grid tbody');const errors=document.getElementById('sheet_errors');const status=document.getElementById('sheet_status');const bar=document.getElementById('sheet_formula');
    const columns=['kode','nama','parent_key','jenis_target','input'];
    const units=['','orang','dokumen','file','nilai','rupiah','persen','prodi','kegiatan','indeks','unit','layanan','jumlah'];
    const el=(tag,className,text)=>{const e=document.createElement(tag);if(className)e.className=className;if(text!==undefined)e.textContent=text;return e;};
    const value=(row,col)=>col===2?(row.parent_key?String(rows.findIndex(r=>r.key===row.parent_key)+1):''):String(row[columns[col]]??'');
    const set=(index,col,text)=>{
        if(col===2){if(rows[index].id||index===0)return;const parent=rows[Number(text)-1];if(parent&&parent!==rows[index])rows[index].parent_key=parent.key;}
        else rows[index][columns[col]]=text;
    };
    function mark(){dirty=true;status.textContent='Perubahan belum disimpan. Menghitung…';schedule();}
    function focusCell(row,col){
        const input=body.querySelector(`[data-row="${row}"][data-col="${col}"]`);
        if(input&&!input.disabled){input.focus();input.scrollIntoView({block:'nearest',inline:'nearest'});}
    }
    function move(event,row,col){
        if(event.key==='Enter'||event.key==='ArrowDown'||event.key==='ArrowUp'){
            event.preventDefault();const next=row+(event.key==='ArrowUp'?-1:1);if(next>=0&&next<rows.length)focusCell(next,col);
        }
    }
    function syncBar(){document.getElementById('sheet_address').textContent=String.fromCharCode(65+active.col)+(active.row+1);bar.value=value(rows[active.row],active.col);bar.disabled=active.col===2&&(rows[active.row].id||active.row===0);}
    function render(){
        body.replaceChildren();
        rows.forEach((row,index)=>{
            const tr=el('tr',index===0?'sheet-root':'');tr.append(el('td','',String(index+1)));
            columns.forEach((column,col)=>{
                const td=el('td');let input;
                if(col===2||col===3){
                    input=el('select');
                    if(col===2){
                        input.add(new Option(index===0?'Sasaran':'Pilih induk',''));
                        rows.forEach((parent,i)=>{if(i!==index)input.add(new Option((i+1)+' — '+(parent.kode||'baru'),parent.key));});
                        input.value=row.parent_key||'';input.disabled=Boolean(row.id)||index===0;
                    }else{[...new Set([...units,row.jenis_target||''])].forEach(unit=>input.add(new Option(unit||'Pilih satuan',unit)));input.value=row.jenis_target||'';}
                }else{input=el(col===1?'textarea':'input',col===4?'sheet-input':'');if(col!==1)input.type='text';input.value=row[column]??'';if(col===1)input.rows=2;input.autocomplete='off';}
                input.dataset.row=index;input.dataset.col=col;input.setAttribute('aria-label',String.fromCharCode(65+col)+(index+1));
                input.addEventListener('focus',()=>{active={row:index,col};syncBar();});
                input.addEventListener(col===2||col===3?'change':'input',()=>{row[column]=input.value;syncBar();mark();});
                input.addEventListener('keydown',event=>move(event,index,col));
                input.addEventListener('paste',event=>paste(event,index,col));
                td.append(input);tr.append(td);
            });
            const result=el('td','sheet-result','—');result.dataset.resultKey=row.key;tr.append(result);body.append(tr);
        });
        document.getElementById('sheet_remove').disabled=rows.length<2||Boolean(rows[rows.length-1].id);
        syncBar();
    }
    function addRow(parentKey){
        if(rows.length>=200){errors.textContent='Maksimal 200 baris per indikator.';return false;}
        const siblings=rows.filter(row=>row.parent_key===parentKey);let code=1;while(siblings.some(row=>row.kode===String(code)))code++;
        rows.push({key:'new-'+Date.now()+'-'+(++counter),id:null,parent_key:parentKey,kode:String(code),nama:'',jenis_target:'orang',input:''});return true;
    }
    function paste(event,row,col){
        const text=event.clipboardData?.getData('text/plain');if(!text||(!text.includes('\n')&&!text.includes('\t')))return;
        event.preventDefault();const matrix=text.replace(/\r/g,'').replace(/\n$/,'').split('\n').map(line=>line.split('\t'));
        for(let i=0;i<matrix.length;i++){
            while(row+i>=rows.length)if(!addRow(rows[0].key))break;
            if(row+i>=rows.length)break;
            matrix[i].forEach((cell,j)=>{if(col+j<columns.length)set(row+i,col+j,cell);});
        }
        active={row,col};render();focusCell(row,col);mark();
    }
    function schedule(){clearTimeout(timer);const request=++version;timer=setTimeout(()=>preview(request),300);}
    async function preview(request){
        try{
            const response=await axios.post('/preview-kontrak-sheet',{jenis:initial.jenis,rows});if(request!==version)return;
            errors.textContent='';body.querySelectorAll('.sheet-invalid').forEach(e=>e.classList.remove('sheet-invalid'));
            response.data.rows.forEach(result=>{const td=[...body.querySelectorAll('[data-result-key]')].find(e=>e.dataset.resultKey===result.key);if(!td)return;td.textContent=result.value===null?'—':result.value.toLocaleString('id-ID',{maximumFractionDigits:2});td.title=result.error||'';});
            const pending=response.data.rows.filter(result=>result.value===null).length;
            status.textContent=(dirty?'Perubahan belum disimpan. ':'')+(pending?pending+' baris belum lengkap; lihat keterangan pada hasil.':'Perhitungan selesai.');
        }catch(error){
            if(request!==version)return;
            const messages=error.response?.data?.errors;errors.textContent=messages?Object.values(messages).flat().join(' '):'Pratinjau belum tersedia. Periksa koneksi.';
            body.querySelectorAll('[data-result-key]').forEach(td=>td.textContent='—');
            Object.keys(messages||{}).forEach(key=>{const match=key.match(/^rows\.(\d+)\.input$/);if(match)body.querySelector(`[data-row="${match[1]}"][data-col="4"]`)?.classList.add('sheet-invalid');});
            status.textContent='Periksa rumus atau isian yang ditandai.';
        }
    }
    bar.addEventListener('input',()=>{set(active.row,active.col,bar.value);const cell=body.querySelector(`[data-row="${active.row}"][data-col="${active.col}"]`);if(cell)cell.value=rows[active.row][columns[active.col]]??'';mark();});
    bar.addEventListener('keydown',event=>move(event,active.row,active.col));bar.addEventListener('paste',event=>paste(event,active.row,active.col));
    document.getElementById('sheet_add').addEventListener('click',()=>{
        const current=rows[active.row];const parent=current.input?.startsWith('=')?current.key:(current.parent_key||rows[0].key);
        if(addRow(parent)){active={row:rows.length-1,col:1};render();focusCell(active.row,1);mark();}
    });
    document.getElementById('sheet_remove').addEventListener('click',()=>{
        if(rows.length<2||rows[rows.length-1].id)return;
        rows.pop();active.row=Math.min(active.row,rows.length-1);render();mark();
    });
    document.getElementById('sheet_indicator').addEventListener('change',event=>{const url=new URL(location.href);url.searchParams.set('indikator',event.target.value);location.href=url.toString();});
    document.getElementById('sheet_save').addEventListener('click',async event=>{
        const button=event.currentTarget;button.disabled=true;errors.textContent='';clearTimeout(timer);++version;
        try{
            const response=await axios.post('/save-kontrak-sheet',{jenis:initial.jenis,id_parent:document.getElementById('sheet_parent').value,rows});
            rows=response.data.rows;dirty=false;render();status.textContent='Tabel berhasil disimpan.';
            const url=new URL(location.href);url.searchParams.set('indikator',response.data.id);history.replaceState({},'',url);
            const selector=document.getElementById('sheet_indicator');if(![...selector.options].some(option=>option.value===String(response.data.id)))selector.add(new Option(rows[0].kode+' — '+rows[0].nama,response.data.id));selector.value=String(response.data.id);document.getElementById('sheet_parent').disabled=true;
            schedule();
        }catch(error){errors.textContent=error.response?.data?.errors?Object.values(error.response.data.errors).flat().join(' '):'Tabel belum tersimpan. Periksa koneksi lalu coba lagi.';status.textContent='Perubahan belum disimpan.';}
        finally{button.disabled=false;}
    });
    render();schedule();
})();
