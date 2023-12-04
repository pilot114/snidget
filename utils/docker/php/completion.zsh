app() {
    local lastParam flagPrefix requestComp out comp
    local -a completions

    words=("${=words[1,CURRENT]}") lastParam=${words[-1]}

    setopt local_options BASH_REMATCH
    if [[ "${lastParam}" =~ '-.*=' ]]; then
        flagPrefix="-P ${BASH_REMATCH}"
    fi

    requestComp="${words[0]} ${words[1]} AutoComplete:run -c $((CURRENT-1))" i=""
    for w in ${words[@]}; do
        w=$(printf -- '%b' "$w")
        quote="${w:0:1}"
        if [ "$quote" = \' ]; then
            w="${w%\'}"
            w="${w#\'}"
        elif [ "$quote" = \" ]; then
            w="${w%\"}"
            w="${w#\"}"
        fi
        if [ ! -z "$w" ]; then
            i="${i}-i ${w} "
        fi
    done

    if [ "${i}" = "" ]; then
        requestComp="${requestComp} -i \" \""
    else
        requestComp="${requestComp} ${i}"
    fi

    out=$(eval ${requestComp} 2>/dev/null)

    while IFS='\n' read -r comp; do
        if [ -n "$comp" ]; then
            comp=${comp//:/\\:}
            local tab=$(printf '\t')
            comp=${comp//$tab/:}
            completions+=${comp}
        fi
    done < <(printf "%s\n" "${out[@]}")

    eval _describe "completions" completions $flagPrefix
    return $?
}

compdef app app
